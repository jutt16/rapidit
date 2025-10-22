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
    public function pay($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->payment_method != 'cod') {

            $booking->payment_method == 'cod';
            $booking->save();
            // return response()->json([
            //     'success' => false,
            //     'message' => 'This endpoint is only for Cash on Delivery payments.',
            // ], 400);
        }

        $payment = BookingPayment::firstOrCreate(
            ['booking_id' => $booking->id],
            ['payment_method' => $booking->payment_method, 'amount' => $booking->total_amount, 'status' => 'paid']
        );

        if (!$payment->wasRecentlyCreated) {
            // record already existed — update the status to paid
            $payment->update(['status' => 'paid']);
        }

        // For COD, we just create a pending record. No external payment link.
        return response()->json([
            'success' => true,
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'message' => 'Cash on Delivery payment initiated. Please collect the amount at delivery.',
            ],
        ]);
    }

    public function markPaid(Request $request, $bookingId)
    {
        $user = $request->user();

        $booking = Booking::findOrFail($bookingId);

        if ($booking->payment_type != 'cod') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for Cash on Delivery payments.',
            ], 400);
        }

        $payment = BookingPayment::where('booking_id', $booking->id)->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No payment record found for this booking.'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Payment is already marked as paid.'], 400);
        }

        $payment->status = 'paid';
        $payment->save();

        return response()->json(['success' => true, 'message' => 'Payment marked as paid successfully.', 'data' => $payment]);
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

    public function update(Request $request, Booking $booking) {}
}
