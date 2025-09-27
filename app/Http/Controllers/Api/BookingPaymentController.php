<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

class BookingPaymentController extends Controller
{
    protected $api;
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = env('RAZORPAY_KEY_ID');
        $this->keySecret = env('RAZORPAY_KEY_SECRET');
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    /**
     * Initiate payment for a booking.
     * If booking->payment_method == 'cod' -> create record (status pending).
     * If 'razorpay' -> create Payment Order, return view.
     */
    public function pay($bookingId)
    {
        $booking = Booking::with('user')->findOrFail($bookingId);

        try {
            // Create Razorpay order
            $order = $this->api->order->create([
                'receipt'  => 'order_rcptid_' . $booking->id,
                'amount'   => $booking->amount * 100, // in paise
                'currency' => 'INR',
            ]);

            // Save initial payment record
            BookingPayment::create([
                'booking_id'         => $booking->id,
                'razorpay_order_id'  => $order['id'],
                'amount'             => $booking->amount,
                'status'             => 'created',
            ]);

            return view('razorpay.pay', [
                'booking'     => $booking,
                'amount'      => $booking->amount * 100,
                'orderId'     => $order['id'],
                'razorpayKey' => $this->keyId,
                'customer'    => [
                    'name'    => $booking->user->name ?? '',
                    'email'   => $booking->user->email ?? '',
                    'contact' => $booking->user->phone ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order create failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Callback (user redirect).
     */
    public function callback(Request $request)
    {
        $attributes = [
            'razorpay_order_id'   => $request->input('razorpay_order_id'),
            'razorpay_payment_id' => $request->input('razorpay_payment_id'),
            'razorpay_signature'  => $request->input('razorpay_signature')
        ];

        try {
            $this->api->utility->verifyPaymentSignature($attributes);

            // Update DB
            $payment = BookingPayment::where('razorpay_order_id', $attributes['razorpay_order_id'])->first();
            if ($payment) {
                $payment->status = 'paid';
                $payment->razorpay_payment_id = $attributes['razorpay_payment_id'];
                $payment->save();

                if ($payment->booking) {
                    $payment->booking->update(['status' => 'confirmed']);
                }
            }

            return view('razorpay-success', ['payment' => $payment]);
        } catch (\Exception $e) {
            return view('razorpay-failure', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Webhook endpoint for server->server events.
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
                    $paymentId = !empty($payments) && isset($payments[0]['id']) ? $payments[0]['id'] : null;

                    $record = BookingPayment::where('razorpay_link_id', $linkId)->first();
                    if ($record) {
                        $record->update([
                            'razorpay_link_status' => $status,
                            'razorpay_payment_id'  => $paymentId ?? $record->razorpay_payment_id,
                            'status'               => $status === 'paid' ? 'paid' : ($status === 'expired' ? 'expired' : $record->status),
                            'meta'                 => array_merge($record->meta ?? [], $entity),
                        ]);

                        if ($status === 'paid' && $record->booking) {
                            $record->booking->update(['status' => 'confirmed']);
                        }
                    }
                }
            }

            // payment.* events
            if (str_starts_with($event, 'payment.')) {
                $entity = $data['payment']['entity'] ?? null;
                if ($entity) {
                    $paymentId = $entity['id'] ?? null;
                    $status = $entity['status'] ?? null;

                    $record = BookingPayment::where('razorpay_payment_id', $paymentId)->first();
                    if (!$record) {
                        $linkId = $entity['notes']['payment_link_id'] ?? null;
                        $record = BookingPayment::where('razorpay_link_id', $linkId)->first();
                    }

                    if ($record) {
                        $record->update([
                            'razorpay_payment_id' => $paymentId,
                            'status'              => $status === 'captured' ? 'paid' : ($status === 'failed' ? 'failed' : $record->status),
                            'meta'                => array_merge($record->meta ?? [], $entity),
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
