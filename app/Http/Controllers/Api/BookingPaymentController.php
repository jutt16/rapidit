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
            $amount = $booking->amount * 100; // Razorpay needs paise

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
                'callback_method' => 'post', // ✅ must be post
            ]);

            // store in DB
            $payment = BookingPayment::create([
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
        $razorpayPaymentId = $request->get('razorpay_payment_id');
        $razorpayLinkId    = $request->get('razorpay_payment_link_id');
        $razorpayStatus    = $request->get('razorpay_payment_link_status');

        try {
            $payment = BookingPayment::where('razorpay_link_id', $razorpayLinkId)->first();

            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment record not found'], 404);
            }

            // fetch real payment link status from Razorpay for safety
            $link = $this->api->paymentLink->fetch($razorpayLinkId);

            if ($link && $link['status'] === 'paid') {
                $payment->update([
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_link_status' => 'paid',
                    'status'               => 'paid',
                ]);

                if ($payment->booking) {
                    $payment->booking->update(['status' => 'confirmed']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful',
                    'data'    => [
                        'booking_id' => $payment->booking_id,
                        'status'     => 'paid',
                    ]
                ]);
            }

            // otherwise failed/expired/cancelled
            $payment->update([
                'razorpay_link_status' => $razorpayStatus,
                'status'               => $razorpayStatus,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment not completed',
                'status'  => $razorpayStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay Callback Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Callback error',
                'error'   => $e->getMessage(),
            ], 500);
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
