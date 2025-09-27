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
     * Create a Razorpay Payment Link for a booking.
     */
    public function pay(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $amount = $booking->amount * 100; // Razorpay requires paise

            $link = $this->api->paymentLink->create([
                'amount' => $amount,
                'currency' => 'INR',
                'description' => "Payment for Booking #{$booking->id}",
                'customer' => [
                    'name'  => $booking->user->name ?? 'Customer',
                    'email' => $booking->user->email ?? 'noemail@example.com',
                    'contact' => $booking->user->phone ?? '9999999999',
                ],
                'notify' => [
                    'sms' => true,
                    'email' => true,
                ],
                'callback_url' => url("/api/payments/callback"),
                'callback_method' => 'post',
            ]);

            BookingPayment::create([
                'booking_id'           => $booking->id,
                'razorpay_link_id'     => $link['id'],
                'razorpay_link_status' => $link['status'],
                'amount'               => $booking->amount,
                'status'               => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment link created successfully',
                'data' => [
                    'payment_link_id'  => $link['id'],
                    'payment_link_url' => $link['short_url'],
                    'status'           => 'pending',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay Pay Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment link',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Razorpay callback (POST).
     */
    public function callback(Request $request)
    {
        return $this->handlePaymentUpdate($request->all(), 'callback');
    }

    /**
     * Razorpay Webhook (POST).
     */
    public function webhook(Request $request)
    {
        // Verify signature (for security)
        $payload    = $request->getContent();
        $signature  = $request->header('X-Razorpay-Signature');
        $secret     = env('RAZORPAY_WEBHOOK_SECRET');

        try {
            $this->api->utility->verifyWebhookSignature($payload, $signature, $secret);
        } catch (\Exception $e) {
            Log::error("Razorpay Webhook Signature Verification Failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        return $this->handlePaymentUpdate($request->all(), 'webhook');
    }

    /**
     * Common handler for callback & webhook.
     */
    private function handlePaymentUpdate($data, $source = 'system')
    {
        try {
            $razorpayPaymentId = $data['razorpay_payment_id'] ?? null;
            $razorpayLinkId    = $data['razorpay_payment_link_id'] ?? null;
            $razorpayStatus    = $data['razorpay_payment_link_status'] ?? null;

            if (!$razorpayLinkId) {
                return response()->json(['success' => false, 'message' => 'Missing link id'], 400);
            }

            $payment = BookingPayment::where('razorpay_link_id', $razorpayLinkId)->first();

            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment record not found'], 404);
            }

            // Fetch from Razorpay API for latest status
            $link = $this->api->paymentLink->fetch($razorpayLinkId);

            if ($link && $link['status'] === 'paid') {
                $payment->update([
                    'razorpay_payment_id'  => $razorpayPaymentId,
                    'razorpay_link_status' => 'paid',
                    'status'               => 'paid',
                ]);

                if ($payment->booking) {
                    $payment->booking->update(['status' => 'confirmed']);
                }

                Log::info("Payment successful via {$source}", $data);

                return response()->json(['success' => true, 'message' => 'Payment successful']);
            }

            // Failed / expired / cancelled
            $payment->update([
                'razorpay_link_status' => $razorpayStatus,
                'status'               => $razorpayStatus ?? 'failed',
            ]);

            Log::warning("Payment not completed via {$source}", $data);

            return response()->json(['success' => false, 'message' => 'Payment not completed']);
        } catch (\Exception $e) {
            Log::error("Razorpay {$source} Error: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Payment update error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel payment manually.
     */
    public function cancel(Request $request, $id)
    {
        $payment = BookingPayment::where('booking_id', $id)->latest()->first();
        if ($payment) {
            $payment->update(['status' => 'cancelled']);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment cancelled',
        ]);
    }

    /**
     * Get payment status for a booking.
     */
    public function status(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = BookingPayment::where('booking_id', $booking->id)->latest()->first();

        return response()->json([
            'success' => true,
            'data'    => $payment,
        ]);
    }
}
