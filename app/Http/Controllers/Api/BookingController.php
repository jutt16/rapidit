<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
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
}
