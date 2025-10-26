<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\BookingCancellationCharge;
use App\Models\BookingPayment;
use App\Models\CookBooking;
use App\Models\PartnerAvailability;
use App\Models\PartnerProfile;
use App\Models\UserAddress;
use App\Models\Setting; // ✅ added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user(); // ✅ Authenticated via Sanctum

            if ($user->role === 'user') {
                // -----------------------------
                // USER: get all their bookings
                // -----------------------------
                $bookings = Booking::with([
                    'service',
                    'address',
                    'cookBooking',
                    'maidPackage',
                    'review',
                ])
                    ->where('user_id', $user->id)
                    ->latest()
                    ->get()
                    ->map(function ($booking) {
                        if (in_array($booking->status, ['accepted', 'confirmed', 'completed'])) {
                            $booking->requests = $booking->requests()
                                ->with('partner.partnerProfile')
                                ->get();
                        } else {
                            unset($booking->requests);
                        }

                        // ✅ add is_paid flag based on BookingPayment status
                        $booking->is_paid = BookingPayment::where('booking_id', $booking->id)
                            ->where('status', 'paid')
                            ->exists();
                        return $booking;
                    });
            } elseif ($user->role === 'partner') {
                // -----------------------------
                // PARTNER: get bookings via requests
                // -----------------------------
                $bookings = Booking::with([
                    'service',
                    'address',
                    'cookBooking',
                    'maidPackage',
                    'review',
                    'user.profile',
                ])
                    ->whereHas('requests', function ($q) use ($user) {
                        $q->where('partner_id', $user->id);
                    })
                    ->latest()
                    ->get()
                    ->map(function ($booking) use ($user) {
                        // Filter only this partner's requests
                        $booking->requests = $booking->requests()
                            ->where('partner_id', $user->id)
                            ->with('partner.partnerProfile')
                            ->get();

                        // ✅ add is_paid flag based on BookingPayment status
                        $booking->is_paid = BookingPayment::where('booking_id', $booking->id)
                            ->where('status', 'paid')
                            ->exists();

                        return $booking;
                    });
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role. Must be user or partner.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data'    => $bookings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bookings.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function cancellations(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Only users can view their cancellation charges.',
            ], 403);
        }

        $charges = BookingCancellationCharge::with('booking')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $charges,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id'     => 'required|exists:services,id',
            'address_id'     => 'required|exists:user_addresses,id',
            'schedule_date'  => 'required|date|after_or_equal:today',
            'schedule_time'  => 'required|string',
            'payment_method' => 'required|string',
            'amount'         => 'required|numeric|min:0',
            'tax'            => 'nullable|numeric|min:0',
            'total_amount'   => 'required|numeric|min:0',
            'service_time'   => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $userId = auth()->id();

            // -----------------------------------------------------
            // 1️⃣ Check if user has already received initial discount
            // -----------------------------------------------------
            $alreadyGotDiscount = Booking::where('user_id', $userId)
                ->where('initial_discount_applied', true)
                ->exists();

            $amount = $request->amount;
            $totalAmount = $request->total_amount;
            $discountApplied = false;

            // -----------------------------------------------------
            // 2️⃣ Apply discount only if user never got it
            // -----------------------------------------------------
            if (!$alreadyGotDiscount) {
                $discountPercent = (float) \App\Models\Setting::get('initial_discount', 0);

                if ($discountPercent > 0) {
                    $discountAmount = round(($amount * $discountPercent) / 100, 2);
                    $amountAfterDiscount = $amount - $discountAmount;

                    $amount = $amountAfterDiscount;
                    $totalAmount = round($amountAfterDiscount + $request->tax, 2);
                    $discountApplied = true;
                }
            }

            // -----------------------------------------------------
            // 3️⃣ Create booking
            // -----------------------------------------------------
            $booking = Booking::create([
                'user_id'                 => $userId,
                'service_id'              => $request->service_id,
                'address_id'              => $request->address_id,
                'schedule_date'           => $request->schedule_date,
                'schedule_time'           => $request->schedule_time,
                'payment_method'          => $request->payment_method,
                'amount'                  => $amount,
                'tax'                     => $request->tax ?? 0,
                'total_amount'            => $totalAmount,
                'service_time'            => $request->service_time,
                'status'                  => 'pending',
                'initial_discount_applied' => $discountApplied,
            ]);

            // -----------------------------------------------------
            // 4️⃣ Dispatch related booking logic (requests, payments, etc.)
            // -----------------------------------------------------
            // Example: send booking request to providers nearby
            // $this->dispatchBookingRequests($booking);

            // Example: handle payment gateway logic
            // $this->processPayment($booking);

            DB::commit();

            // -----------------------------------------------------
            // 5️⃣ Return JSON or redirect as before
            // -----------------------------------------------------
            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'booking' => $booking,
                'initial_discount_applied' => $discountApplied,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating booking.',
            ], 500);
        }
    }

    /**
     * Reschedule a booking (user only)
     */
    public function reschedule(Request $request, $id)
    {
        $user = $request->user();

        // 1. Validation
        $validator = Validator::make($request->all(), [
            'schedule_date' => 'required|date',
            'schedule_time' => 'required|string', // format "HH:MM-HH:MM"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2. Find booking
        $booking = Booking::where('id', $id)
            ->where('user_id', $user->id) // only user can reschedule their own booking
            ->firstOrFail();

        // 3. Check if any request is already accepted
        $hasAccepted = BookingRequest::where('booking_id', $booking->id)
            ->where('status', 'accepted')
            ->exists();

        if ($hasAccepted) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is already accepted by a partner and cannot be rescheduled.',
            ], 422);
        }

        // 4. Update booking
        $booking->update([
            'schedule_date' => $request->schedule_date,
            'schedule_time' => $request->schedule_time,
            'status'        => 'pending', // reset status after reschedule
        ]);

        // 5. Expire all old requests (since time changed)
        BookingRequest::where('booking_id', $booking->id)
            ->whereIn('status', ['pending', 'expired'])
            ->update(['status' => 'expired']);

        // ⚡ Optionally: re-run partner search & send fresh requests 
        // (same as in store() method)
        // -- If you want this, I can extract your partner search logic into a private method 
        //    and reuse here to avoid code duplication.

        return response()->json([
            'success' => true,
            'message' => 'Booking rescheduled successfully.',
            'data'    => $booking->fresh(),
        ]);
    }

    public function completed($id)
    {
        $booking = Booking::with(['requests.partner', 'service'])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        $acceptedRequest = $booking->requests()
            ->where('status', 'accepted')
            ->with('partner')
            ->first();

        if (!$acceptedRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Booking is not accepted by any partner.',
            ], 422);
        }

        // ✅ Mark booking as completed
        // $booking->update(['status' => 'completed']);
        $booking->status = 'completed';
        $booking->save();
        $booking->refresh();


        $partner = $acceptedRequest->partner;
        $wallet = \App\Models\Wallet::firstOrCreate(['user_id' => $partner->id], ['balance' => 0]);

        // ✅ Calculate commission and partner earning
        $service = $booking->service;
        $commissionPct = $service->commission_pct ?? 0;
        $commissionAmount = round($booking->amount * ($commissionPct / 100), 2);
        $partnerEarning = round($booking->amount - $commissionAmount, 2);

        try {
            if ($booking->payment_method === 'razorpay') {
                // ✅ Razorpay: Credit only (net amount after commission)
                $wallet->credit($partnerEarning, "Earning (after commission) for booking #{$booking->id}");
            } elseif ($booking->payment_method === 'cod') {
                // ✅ COD: Credit full amount, then debit full amount + commission (can go negative)
                $wallet->credit($booking->amount, "COD collection for booking #{$booking->id}");

                // force debit even if insufficient balance
                $wallet->balance -= ($booking->amount + $commissionAmount);
                $wallet->save();

                $wallet->transactions()->create([
                    'type' => 'debit',
                    'amount' => $booking->amount + $commissionAmount,
                    'description' => "COD settlement & commission deduction for booking #{$booking->id}",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking completed and wallet updated successfully.',
                'data' => [
                    'booking_id' => $booking->id,
                    'payment_method' => $booking->payment_method,
                    'credited_amount' => $booking->payment_method === 'razorpay' ? $partnerEarning : $booking->amount,
                    'commission' => $commissionAmount,
                    'final_wallet_balance' => $wallet->balance,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking completed but wallet transaction failed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // public function cancel(Request $request, $id)
    // {
    //     $user = $request->user();

    //     $booking = Booking::with('service')->where('id', $id)
    //         ->where('user_id', $user->id)
    //         ->firstOrFail();

    //     // prevent double cancel
    //     if (in_array($booking->status, ['completed', 'cancelled'])) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'This booking is already completed or cancelled.',
    //         ], 422);
    //     }

    //     $requests = $booking->requests;
    //     $accepted = $requests->where('status', 'accepted')->first();

    //     if ($accepted) {
    //         // ✅ Case 1: accepted request exists
    //         $charge = $booking->service->cancellation_charges ?? 0;

    //         if ($charge > 0) {
    //             BookingCancellationCharge::create([
    //                 'booking_id' => $booking->id,
    //                 'user_id'    => $user->id,
    //                 'amount'     => $charge,
    //             ]);
    //         }

    //         // mark accepted one as cancelled
    //         $accepted->update(['status' => 'cancelled']);

    //         // mark others expired
    //         $requests->where('id', '!=', $accepted->id)
    //             ->each->update(['status' => 'expired']);

    //         $booking->update(['status' => 'cancelled']);
    //     } else {
    //         // ✅ Case 2: no accepted → no charges
    //         $requests->each->update(['status' => 'expired']);
    //         $booking->update(['status' => 'cancelled']);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Booking cancelled successfully.',
    //         'data'    => $booking->fresh(),
    //     ]);
    // }
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::with(['service', 'requests'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // prevent double cancel
        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is already completed or cancelled.',
            ], 422);
        }

        $requests = $booking->requests;
        $accepted = $requests->where('status', 'accepted')->first();

        if ($accepted) {
            // ✅ Case 1: accepted request exists
            $charge = $booking->service->cancellation_charges ?? 0;

            // Check if payment exists and paid
            $payment = \App\Models\BookingPayment::where('booking_id', $booking->id)
                ->where('status', 'paid')
                ->first();

            if ($payment) {
                // ✅ Payment already made
                $refundAmount = max($payment->amount - $charge, 0);

                if ($refundAmount > 0) {
                    $wallet = \App\Models\Wallet::firstOrCreate(
                        ['user_id' => $user->id],
                        ['balance' => 0]
                    );

                    // Credit remaining amount
                    $wallet->credit($refundAmount, "Refund for cancelled booking #{$booking->id}");
                }

                if ($charge > 0) {
                    // Log cancellation charge transaction
                    $wallet = \App\Models\Wallet::firstOrCreate(
                        ['user_id' => $user->id],
                        ['balance' => 0]
                    );

                    $wallet->debit($charge, "Cancellation charge for booking #{$booking->id}");
                }
            } else {
                // ✅ No payment → record cancellation charge as before
                if ($charge > 0) {
                    \App\Models\BookingCancellationCharge::create([
                        'booking_id' => $booking->id,
                        'user_id'    => $user->id,
                        'amount'     => $charge,
                    ]);
                }
            }

            // mark accepted one as cancelled
            $accepted->update(['status' => 'cancelled']);

            // mark others expired
            $requests->where('id', '!=', $accepted->id)
                ->each->update(['status' => 'expired']);

            $booking->update(['status' => 'cancelled']);
        } else {
            // ✅ Case 2: no accepted → no charges
            $requests->each->update(['status' => 'expired']);
            $booking->update(['status' => 'cancelled']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data'    => $booking->fresh(),
        ]);
    }
}
