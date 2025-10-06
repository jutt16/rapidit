<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\BookingCancellationCharge;
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

    public function store(Request $request)
    {
        try {
            // 1. Validation rules
            $rules = [
                'service_id'     => 'required|exists:services,id',
                'address_id'     => 'required|exists:user_addresses,id',
                'schedule_date'  => 'required|date',
                'schedule_time'  => 'required|string', // format: "15:00-16:00"
                'payment_method' => 'required|in:cod,razorpay',
                'amount'         => 'required|numeric',
                'tax'            => 'required|numeric',
                'total_amount'   => 'required|numeric',
                'service_time'   => 'nullable|integer', // only for maid
            ];

            // If cook service, add cook-specific fields
            if ($request->service_id == 6) { // 6 = Cook service
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

            // Wrap in transaction to keep DB consistent
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $userId = $user->id; // ✅ always from auth

                // 2. Get user address
                $address = UserAddress::find($request->address_id);
                if (!$address) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Address not found',
                    ], 404);
                }

                // 3. Create booking
                $booking = Booking::create([
                    'user_id'        => $userId,
                    'service_id'     => $request->service_id,
                    'address_id'     => $request->address_id,
                    'schedule_date'  => $request->schedule_date,
                    'schedule_time'  => $request->schedule_time,
                    'payment_method' => $request->payment_method,
                    'amount'         => $request->amount,
                    'tax'            => $request->tax,
                    'total_amount'   => $request->total_amount,
                    'service_time'   => $request->service_time,
                    'status'         => 'pending',
                ]);

                // 4. If Cook service → create CookBooking record
                if ($request->service_id == 6) {
                    CookBooking::create([
                        'booking_id'   => $booking->id,
                        'no_of_people' => $request->no_of_people,
                        'food_type1'   => $request->food_type1,
                        'food_type2'   => $request->food_type2,
                        'no_of_dishes' => $request->no_of_dishes,
                    ]);
                }

                // 5. Find nearby partners (Haversine)
                $lat = $address->latitude;
                $lng = $address->longitude;

                // ✅ radius from settings table; fallback = 10 km
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

                // 6. Filter available partners (using model method)
                $availablePartners = $partners->filter(function ($partner) use ($request) {
                    $availability = PartnerAvailability::where('partner_id', $partner->user_id)->first();
                    return $availability && $availability->isAvailableFor($request->schedule_date, $request->schedule_time);
                });

                // 7. Attach booking requests
                foreach ($availablePartners as $partner) {
                    BookingRequest::create([
                        'booking_id' => $booking->id,
                        'partner_id' => $partner->user_id,
                        'status'     => 'pending',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'data'    => [
                        'booking'  => $booking,
                        'partners' => $availablePartners->pluck('user_id'),
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the booking.',
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
        $booking->update(['status' => 'completed']);

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

    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::with('service')->where('id', $id)
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

            if ($charge > 0) {
                BookingCancellationCharge::create([
                    'booking_id' => $booking->id,
                    'user_id'    => $user->id,
                    'amount'     => $charge,
                ]);
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
