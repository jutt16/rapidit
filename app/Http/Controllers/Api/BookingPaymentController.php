<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookingPaymentController extends Controller
{
    protected $api;
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID'));
        $this->keySecret = config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET'));
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    /**
     * Initiate payment for a booking.
     * If booking->payment_method == 'cod' -> create record (status pending).
     * If 'razorpay' -> create Payment Link, return URL.
     */
    public function pay(Request $request, Booking $booking)
    {
        $user = User::findorFail(4);//$request->user();

        if ($booking->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $amount = intval($booking->total_amount * 100); // in paise

        $api = new \Razorpay\Api\Api(
            env('RAZORPAY_KEY_ID'),
            env('RAZORPAY_KEY_SECRET')
        );

        // Create order on Razorpay
        $order = $api->order->create([
            'receipt' => 'booking_' . $booking->id,
            'amount' => $amount,
            'currency' => 'INR',
        ]);

        // Save to DB
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'payment_method' => 'razorpay',
            'amount' => $booking->total_amount,
            'status' => 'pending',
            'razorpay_order_id' => $order['id'],
        ]);

        // Send variables to view
        return view('razorpay-checkout', [
            'booking' => $booking,
            'key' => env('RAZORPAY_KEY_ID'),
            'amount' => $amount,
            'order_id' => $order['id'],
        ]);
    }

    /**
     * Callback (user redirect) - GET. Razorpay will redirect the WebView to this URL with query params:
     * razorpay_payment_id, razorpay_payment_link_id, razorpay_payment_link_reference_id,
     * razorpay_payment_link_status, razorpay_signature
     */
    public function callback(Request $request)
    {
        $api = new \Razorpay\Api\Api(
            env('RAZORPAY_KEY_ID'),
            env('RAZORPAY_KEY_SECRET')
        );

        $attributes = [
            'razorpay_order_id' => $request->input('razorpay_order_id'),
            'razorpay_payment_id' => $request->input('razorpay_payment_id'),
            'razorpay_signature' => $request->input('razorpay_signature')
        ];

        try {
            $api->utility->verifyPaymentSignature($attributes);

            // Update DB
            $payment = BookingPayment::where('razorpay_order_id', $request->input('razorpay_order_id'))->first();
            if ($payment) {
                $payment->status = 'paid';
                $payment->razorpay_payment_id = $request->input('razorpay_payment_id');
                $payment->save();
            }

            return view('razorpay-success', ['payment' => $payment]);
        } catch (\Exception $e) {
            return view('razorpay-failure', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Webhook endpoint for server->server events.
     * Verify header X-Razorpay-Signature using RAZORPAY_WEBHOOK_SECRET.
     */
    public function webhook(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('x-razorpay-signature');

        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
        if (!$webhookSecret) {
            Log::error('Webhook secret not configured');
            return response()->json(['success' => false, 'message' => 'Webhook secret not configured'], 500);
        }

        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        if (!hash_equals($expected, (string)$signature)) {
            Log::warning('Invalid webhook signature', ['expected' => $expected, 'received' => $signature]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $payload = json_decode($rawBody, true);
        $event = $payload['event'] ?? null;
        $data = $payload['payload'] ?? [];

        try {
            // payment_link.* events
            if (str_starts_with($event, 'payment_link.')) {
                $entity = $data['payment_link']['entity'] ?? null;
                if ($entity) {
                    $linkId = $entity['id'] ?? null;
                    $status = $entity['status'] ?? null;
                    $payments = $entity['payments'] ?? [];
                    $paymentId = null;
                    if (!empty($payments) && isset($payments[0]['id'])) {
                        $paymentId = $payments[0]['id'];
                    }

                    $record = BookingPayment::where('razorpay_link_id', $linkId)->first();
                    if ($record) {
                        // idempotent update
                        $record->update([
                            'razorpay_link_status' => $status,
                            'razorpay_payment_id' => $paymentId ?? $record->razorpay_payment_id,
                            'status' => $status === 'paid' ? 'paid' : ($status === 'expired' ? 'expired' : $record->status),
                            'meta' => array_merge($record->meta ?? [], $entity),
                        ]);
                        if ($status === 'paid' && $record->booking) {
                            $record->booking->update(['status' => 'confirmed']);
                        }
                    }
                }
            }

            // payment.* events (fallback)
            if (str_starts_with($event, 'payment.')) {
                $entity = $data['payment']['entity'] ?? null;
                if ($entity) {
                    $paymentId = $entity['id'] ?? null;
                    $status = $entity['status'] ?? null; // captured, failed, authorized...
                    // try find by payment id
                    $record = BookingPayment::where('razorpay_payment_id', $paymentId)->first();
                    if (!$record) {
                        // maybe link id is inside notes
                        $linkId = $entity['notes']['payment_link_id'] ?? null;
                        $record = BookingPayment::where('razorpay_link_id', $linkId)->first();
                    }
                    if ($record) {
                        $record->update([
                            'razorpay_payment_id' => $paymentId,
                            'status' => $status === 'captured' ? 'paid' : ($status === 'failed' ? 'failed' : $record->status),
                            'meta' => array_merge($record->meta ?? [], $entity),
                        ]);
                        if ($status === 'captured' && $record->booking) {
                            $record->booking->update(['status' => 'confirmed']);
                        }
                    }
                }
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), ['payload' => $payload]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get payment status for a booking (for frontend polling)
     */
    public function status(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = BookingPayment::where('booking_id', $booking->id)->latest()->first();

        return response()->json(['success' => true, 'data' => $payment]);
    }
}
