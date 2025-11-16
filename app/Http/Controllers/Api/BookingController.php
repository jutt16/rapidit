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
use App\Services\ZoneCoverageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    protected ZoneCoverageService $zoneCoverage;

    public function __construct(ZoneCoverageService $zoneCoverage)
    {
        $this->zoneCoverage = $zoneCoverage;
    }

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
        try {
            // -------------------------
            // 1️⃣ Validation
            // -------------------------
            $rules = [
                'service_id'     => 'required|exists:services,id',
                'address_id'     => 'required|exists:user_addresses,id',
                'schedule_date'  => 'required|date|after_or_equal:today',
                'schedule_time'  => 'required|string', // format: "15:00-16:00"
                'payment_method' => 'required|in:cod,razorpay',
                'amount'         => 'required|numeric|min:0',
                'tax'            => 'nullable|numeric|min:0',
                'total_amount'   => 'required|numeric|min:0',
                'service_time'   => 'nullable|integer',
            ];

            // Cook-specific validation
            if ($request->service_id == 6) {
                $rules = array_merge($rules, [
                    'no_of_people' => 'required|integer|min:1',
                    'food_type1'   => 'required|string',
                    'food_type2'   => 'required|string',
                    'no_of_dishes' => 'required|integer|min:1',
                ]);
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $user = $request->user();
            $userId = $user->id;

            // -------------------------
            // 2️⃣ Check initial discount
            // -------------------------
            $alreadyGotDiscount = Booking::where('user_id', $userId)
                ->where('initial_discount_applied', true)
                ->exists();

            $amount = $request->amount;
            $originalAmount = $amount; // Store original amount before discount
            $totalAmount = $request->total_amount;
            $discountApplied = false;

            if (!$alreadyGotDiscount) {
                $discountPercent = (float) Setting::get('initial_discount', 0);
                if ($discountPercent > 0) {
                    $discountAmount = round(($amount * $discountPercent) / 100, 2);
                    
                    // Apply "upto_initial_discount" cap if set
                    $uptoInitialDiscount = (float) Setting::get('upto_initial_discount', 0);
                    if ($uptoInitialDiscount > 0 && $discountAmount > $uptoInitialDiscount) {
                        $discountAmount = $uptoInitialDiscount;
                    }
                    
                    // Ensure discount doesn't exceed the original amount
                    if ($discountAmount > $amount) {
                        $discountAmount = $amount;
                    }
                    
                    $amountAfterDiscount = $amount - $discountAmount;
                    // Ensure amount after discount is never negative
                    $amountAfterDiscount = max(0, $amountAfterDiscount);

                    $amount = $amountAfterDiscount;
                    $totalAmount = round($amountAfterDiscount + ($request->tax ?? 0), 2);
                    $discountApplied = true;
                }
            }

            // -------------------------
            // 3️⃣ Get user address
            // -------------------------
            $address = UserAddress::find($request->address_id);
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found',
                ], 404);
            }

            if ($address->latitude === null || $address->longitude === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected address is missing coordinates. Please update the address location.',
                ], 422);
            }

            $this->zoneCoverage->assertWithinActiveZone(
                (float) $address->latitude,
                (float) $address->longitude,
                'address_id'
            );

            // -------------------------
            // 4️⃣ Create booking
            // -------------------------
            $booking = Booking::create([
                'user_id'                  => $userId,
                'service_id'               => $request->service_id,
                'address_id'               => $request->address_id,
                'schedule_date'            => $request->schedule_date,
                'schedule_time'            => $request->schedule_time,
                'payment_method'           => $request->payment_method,
                'amount'                   => $amount,
                'original_amount'          => $originalAmount, // Store original amount before discount
                'tax'                      => $request->tax ?? 0,
                'total_amount'             => $totalAmount,
                'service_time'             => $request->service_time,
                'status'                   => 'pending',
                'initial_discount_applied' => $discountApplied,
            ]);

            // -------------------------
            // 5️⃣ Cook-specific booking
            // -------------------------
            if ($request->service_id == 6) {
                CookBooking::create([
                    'booking_id'   => $booking->id,
                    'no_of_people' => $request->no_of_people,
                    'food_type1'   => $request->food_type1,
                    'food_type2'   => $request->food_type2,
                    'no_of_dishes' => $request->no_of_dishes,
                ]);
            }

            // -------------------------
            // 6️⃣ Find nearby partners
            // -------------------------
            $lat = $address->latitude;
            $lng = $address->longitude;
            $radius = (float) (Setting::where('key', 'search_radius_km')->value('value') ?? 10);

            $partners = PartnerProfile::selectRaw("
            partner_profiles.*, 
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
                ->having("distance", "<", $radius)
                ->orderBy("distance")
                ->get();

            // -------------------------
            // 7️⃣ Filter available partners
            // -------------------------
            $availablePartners = $partners->filter(function ($partner) use ($request) {
                $availability = PartnerAvailability::where('partner_id', $partner->user_id)->first();
                if (!$availability) return false;

                if (!empty($request->schedule_time)) {
                    return $availability->isAvailableFor($request->schedule_date, $request->schedule_time);
                }
                return $availability->isAvailableForDateOnly($request->schedule_date);
            });

            // -------------------------
            // 8️⃣ Create booking requests
            // -------------------------
            foreach ($availablePartners as $partner) {
                $request = BookingRequest::create([
                    'booking_id' => $booking->id,
                    'partner_id' => $partner->user_id,
                    'status'     => 'pending',
                ]);
                
                // Send notification to partner
                $partnerUser = \App\Models\User::find($partner->user_id);
                if ($partnerUser) {
                    app(\App\Services\FcmService::class)->sendToUser(
                        $partnerUser,
                        'New Booking Request',
                        "You have a new booking request for {$booking->service->name}",
                        [
                            'type' => 'new_booking_request',
                            'booking_id' => (string)$booking->id,
                            'request_id' => (string)$request->id,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'booking' => $booking,
                'initial_discount_applied' => $discountApplied,
                'partners' => $availablePartners->pluck('user_id'),
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating booking.',
                'error'   => $e->getMessage(),
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

        // ✅ Determine the amount to use for partner payment
        // COD: Use discounted amount (what customer actually paid)
        // Razorpay: Use original amount (before discount, what customer was supposed to pay)
        $partnerAmount = $booking->payment_method === 'razorpay' 
            ? ($booking->original_amount ?? $booking->amount) // Use original amount for Razorpay
            : $booking->amount; // Use discounted amount for COD

        // ✅ Calculate commission and partner earning
        $service = $booking->service;
        $commissionPct = $service->commission_pct ?? 0;
        $commissionAmount = round($partnerAmount * ($commissionPct / 100), 2);
        $partnerEarning = round($partnerAmount - $commissionAmount, 2);

        try {
            if ($booking->payment_method === 'razorpay') {
                // ✅ Razorpay: Credit full original amount (after commission)
                $wallet->credit($partnerEarning, "Earning (after commission) for booking #{$booking->id} - Original amount: ₹{$partnerAmount}");
            } elseif ($booking->payment_method === 'cod') {
                // ✅ COD: Credit discounted amount (what customer actually paid), then debit commission only
                // Partner collects discounted amount from customer, gets discounted amount - commission
                $wallet->credit($booking->amount, "COD collection for booking #{$booking->id}");

                // Debit only commission (not the full amount)
                $wallet->debit($commissionAmount, "Commission deduction for booking #{$booking->id}");
            }

            // Send notifications to both user and partner
            $bookingUser = $booking->user;
            if ($bookingUser) {
                app(\App\Services\FcmService::class)->sendToUser(
                    $bookingUser,
                    'Booking Completed',
                    "Your booking has been completed successfully",
                    [
                        'type' => 'booking_completed',
                        'booking_id' => (string)$booking->id,
                    ]
                );
            }

            if ($partner) {
                app(\App\Services\FcmService::class)->sendToUser(
                    $partner,
                    'Booking Completed',
                    "You have earned ₹{$partnerEarning} from booking #{$booking->id}",
                    [
                        'type' => 'booking_completed',
                        'booking_id' => (string)$booking->id,
                        'earning' => (string)$partnerEarning,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking completed and wallet updated successfully.',
                'data' => [
                    'booking_id' => $booking->id,
                    'payment_method' => $booking->payment_method,
                    'original_amount' => $booking->original_amount ?? $booking->amount,
                    'discounted_amount' => $booking->amount,
                    'partner_amount' => $partnerAmount,
                    'credited_amount' => $booking->payment_method === 'razorpay' ? $partnerEarning : $booking->amount,
                    'commission' => $commissionAmount,
                    'partner_earning' => $partnerEarning,
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

            // Send notification to user
            app(\App\Services\FcmService::class)->sendToUser(
                $user,
                'Booking Cancelled',
                'Your booking has been cancelled' . ($charge > 0 ? '. Cancellation charges may apply.' : '.'),
                [
                    'type' => 'booking_cancelled',
                    'booking_id' => (string)$booking->id,
                ]
            );

            // Send notification to partner
            if ($accepted) {
                $partner = $accepted->partner;
                if ($partner) {
                    app(\App\Services\FcmService::class)->sendToUser(
                        $partner,
                        'Booking Cancelled',
                        "Booking #{$booking->id} has been cancelled by the customer",
                        [
                            'type' => 'booking_cancelled',
                            'booking_id' => (string)$booking->id,
                        ]
                    );
                }
            }
        } else {
            // ✅ Case 2: no accepted → no charges
            $requests->each->update(['status' => 'expired']);
            $booking->update(['status' => 'cancelled']);

            // Send notification to user
            app(\App\Services\FcmService::class)->sendToUser(
                $user,
                'Booking Cancelled',
                'Your booking has been cancelled successfully',
                [
                    'type' => 'booking_cancelled',
                    'booking_id' => (string)$booking->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data'    => $booking->fresh(),
        ]);
    }
}
