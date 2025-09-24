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
        $this->keyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID'));
        $this->keySecret = config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET'));
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    /**
     * Initiate Razorpay payment
     */
    public function pay(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($booking->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $amount = intval($booking->total_amount * 100); // paise

        $order = $this->api->order->create([
            'receipt'  => 'booking_' . $booking->id,
            'amount'   => $amount,
            'currency' => 'INR',
        ]);

        BookingPayment::create([
            'booking_id'        => $booking->id,
            'payment_method'    => 'razorpay',
            'amount'            => $booking->total_amount, // store in ₹
            'status'            => 'pending',
            'razorpay_order_id' => $order['id'],
        ]);

        return view('razorpay-checkout', [
            'booking'  => $booking,
            'key'      => $this->keyId,
            'amount'   => $amount,
            'order_id' => $order['id'],
        ]);
    }

    /**
     * Payment callback (user redirect)
     */
    public function callback(Request $request)
    {
        $attributes = [
            'razorpay_order_id'   => $request->input('razorpay_order_id'),
            'razorpay_payment_id' => $request->input('razorpay_payment_id'),
            'razorpay_signature'  => $request->input('razorpay_signature'),
        ];

        try {
            $this->api->utility->verifyPaymentSignature($attributes);

            $payment = BookingPayment::where('razorpay_order_id', $request->input('razorpay_order_id'))->first();
            if ($payment) {
                $payment->update([
                    'status'              => 'paid',
                    'razorpay_payment_id' => $request->input('razorpay_payment_id'),
                ]);

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
     * Webhook (server-to-server)
     */
    public function webhook(Request $request)
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('x-razorpay-signature');
        $secret    = env('RAZORPAY_WEBHOOK_SECRET');

        if (!$secret) {
            return response()->json(['success' => false, 'message' => 'Webhook secret not configured'], 500);
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);
        if (!hash_equals($expected, (string) $signature)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $payload = json_decode($rawBody, true);
        $event   = $payload['event'] ?? null;

        try {
            if (str_starts_with($event, 'payment.')) {
                $entity    = $payload['payload']['payment']['entity'] ?? null;
                $paymentId = $entity['id'] ?? null;
                $status    = $entity['status'] ?? null;

                $record = BookingPayment::where('razorpay_payment_id', $paymentId)->first();
                if ($record) {
                    $record->update([
                        'status' => $status === 'captured' ? 'paid' : ($status === 'failed' ? 'failed' : $record->status),
                    ]);

                    if ($status === 'captured' && $record->booking) {
                        $record->booking->update(['status' => 'confirmed']);
                    }
                }
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Check payment status
     */
    public function status(Booking $booking)
    {
        $payment = BookingPayment::where('booking_id', $booking->id)->latest()->first();
        return response()->json([
            'status'  => $payment ? $payment->status : 'not_found',
            'payment' => $payment,
        ]);
    }
}
