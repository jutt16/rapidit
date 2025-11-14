<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Exception;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class RazorpayPaymentController extends Controller
{
    private $razorpayApi;

    public function __construct()
    {
        $this->razorpayApi = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_SECRET'));
    }

    /**
     * Show the Razorpay payment page.
     */
    public function index($bookingId): View|RedirectResponse
    {
        try {
            Log::info('Entered RazorpayPaymentController', ['bookingId' => $bookingId]);

            $booking = Booking::with('user')->findOrFail($bookingId);

            if ($booking->payment_method !== "razorpay") {
                return redirect()->back()->with("error", "Invalid payment method");
            }

            $existingPayment = BookingPayment::where('booking_id', $bookingId)
                ->where('status', 'paid')
                ->first();

            if ($existingPayment) {
                return redirect()->route('razorpay.status', $bookingId);
            }

            $orderData = [
                'receipt' => 'booking_' . $booking->id,
                'amount' => $booking->total_amount * 100,
                'currency' => 'INR',
                'payment_capture' => 1
            ];

            $razorpayOrder = $this->razorpayApi->order->create($orderData);
            $orderId = $razorpayOrder['id'];

            return view('razorpay.pay', compact('booking', 'orderId'));
        } catch (\Throwable $e) {
            Log::error('RazorpayPaymentController@index failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            echo 'Error occurred: ' . $e->getMessage();
            // dd('Error occurred', $e->getMessage());
        }
    }

    /**
     * Handle Razorpay payment callback
     */
    public function handleCallback(Request $request, $payment_data): RedirectResponse
    {
        try {
            $bookingId = base64_decode($payment_data);
            $booking   = Booking::findOrFail($bookingId);

            $input = $request->all();
            Log::info('Razorpay callback received:', $input);

            if (empty($input['razorpay_payment_id']) || empty($input['razorpay_signature'])) {
                throw new Exception('Missing payment parameters');
            }

            $attributes = [
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_order_id'   => $input['razorpay_order_id'] ?? null,
                'razorpay_signature'  => $input['razorpay_signature']
            ];

            if (!empty($input['razorpay_order_id'])) {
                $this->razorpayApi->utility->verifyPaymentSignature($attributes);
            }

            $payment = $this->razorpayApi->payment->fetch($input['razorpay_payment_id']);

            if ($payment->status === 'captured') {
                BookingPayment::updateOrCreate(
                    ['booking_id' => $bookingId],
                    [
                        'payment_method'      => 'razorpay',
                        'amount'              => $booking->amount,
                        'razorpay_payment_id' => $input['razorpay_payment_id'],
                        'razorpay_order_id'   => $input['razorpay_order_id'] ?? null,
                        'razorpay_signature'  => $input['razorpay_signature'],
                        'status'              => 'paid',
                        'meta'                => json_encode($payment->toArray())
                    ]
                );

                $booking->update(['status' => 'confirmed']);

                // Send payment confirmation notification to user
                $bookingUser = $booking->user;
                if ($bookingUser) {
                    app(\App\Services\FcmService::class)->sendToUser(
                        $bookingUser,
                        'Payment Successful',
                        "Your payment of ₹{$booking->total_amount} for booking #{$bookingId} has been confirmed",
                        [
                            'type' => 'payment_confirmed',
                            'booking_id' => (string)$bookingId,
                            'amount' => (string)$booking->total_amount,
                        ]
                    );
                }

                // ✅ Redirect to new status page
                return redirect()->route('razorpay.status', $bookingId);
            } else {
                BookingPayment::create([
                    'booking_id'          => $bookingId,
                    'payment_method'      => 'razorpay',
                    'amount'              => $booking->amount,
                    'razorpay_payment_id' => $input['razorpay_payment_id'],
                    'razorpay_order_id'   => $input['razorpay_order_id'] ?? null,
                    'razorpay_signature'  => $input['razorpay_signature'],
                    'status'              => 'failed',
                    'meta'                => json_encode($payment->toArray())
                ]);

                return redirect()->route('razorpay.status', $bookingId);
            }
        } catch (Exception $e) {
            Log::error('Razorpay callback error: ' . $e->getMessage());

            // ✅ always show status page, even on error
            return redirect()->route('razorpay.status', base64_decode($payment_data))
                ->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual verification method
     */
    public function verifyPayment(Request $request): RedirectResponse
    {
        try {
            $input     = $request->all();
            $bookingId = $request->booking_id;

            Log::info('Manual verification:', $input);

            $attributes = [
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_order_id'   => $input['razorpay_order_id'],
                'razorpay_signature'  => $input['razorpay_signature']
            ];

            $this->razorpayApi->utility->verifyPaymentSignature($attributes);

            $booking = Booking::findOrFail($bookingId);

            BookingPayment::create([
                'booking_id'          => $bookingId,
                'payment_method'      => 'razorpay',
                'amount'              => $booking->amount,
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_order_id'   => $input['razorpay_order_id'],
                'razorpay_signature'  => $input['razorpay_signature'],
                'status'              => 'paid',
            ]);

            $booking->update(['payment_status' => 'paid']);

            // Send payment confirmation notification to user
            $bookingUser = $booking->user;
            if ($bookingUser) {
                app(\App\Services\FcmService::class)->sendToUser(
                    $bookingUser,
                    'Payment Successful',
                    "Your payment of ₹{$booking->total_amount} for booking #{$bookingId} has been confirmed",
                    [
                        'type' => 'payment_confirmed',
                        'booking_id' => (string)$bookingId,
                        'amount' => (string)$booking->total_amount,
                    ]
                );
            }

            // ✅ Redirect to status page after manual verification
            return redirect()->route('razorpay.status', $bookingId);
        } catch (Exception $e) {
            Log::error('Manual verification error: ' . $e->getMessage());
            return redirect()->route('razorpay.status', $request->booking_id)
                ->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Show payment status page
     */
    public function status($bookingId): View
    {
        $booking = Booking::with('user')->findOrFail($bookingId);
        $payment = BookingPayment::where('booking_id', $bookingId)->latest()->first();
        $status  = $payment && $payment->status === 'paid' ? 'success' : 'failed';

        return view('razorpay.status', compact('booking', 'payment', 'status'));
    }
}
