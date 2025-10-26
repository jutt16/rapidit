<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PartnerProfile;
use App\Models\Service;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PartnerStatsController extends Controller
{
    public function stats(Request $request)
    {
        try {
            $partnerId = $request->query('partner_id');

            if (!$partnerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'partner_id is required'
                ], 422);
            }

            // Get partner profile
            $profile = PartnerProfile::where('user_id', $partnerId)->first();

            // Average rating (fallback to 0)
            $averageRating = $profile->average_rating ?? 0;

            // Total completed jobs
            $totalJobsCompleted = Booking::whereHas('requests', function ($q) use ($partnerId) {
                $q->where('partner_id', $partnerId)
                    ->where('status', 'accepted');
            })
                ->where('status', 'completed')
                ->count();

            /**
             * On-time arrival calculation
             * schedule_time format: "14:00-16:00"
             * actual_arrival_time format: "HH:MM"
             */
            $onTimeBookings = Booking::whereHas('requests', function ($q) use ($partnerId) {
                $q->where('partner_id', $partnerId)
                    ->where('status', 'accepted');
            })
                ->where('status', 'completed')
                ->get()
                ->filter(function ($booking) {
                    if (!$booking->schedule_time || !$booking->actual_arrival_time) {
                        return false;
                    }

                    try {
                        // Extract start time from schedule_time (e.g. "14:00" from "14:00-16:00")
                        [$startTime] = explode('-', $booking->schedule_time);

                        $scheduledStart = Carbon::createFromFormat('H:i', trim($startTime));
                        $actualArrival  = Carbon::createFromFormat('H:i', trim($booking->actual_arrival_time));

                        // Count as on-time if actual <= scheduled start
                        return $actualArrival->lessThanOrEqualTo($scheduledStart);
                    } catch (\Exception $e) {
                        // Ignore any bad formatting
                        return false;
                    }
                })
                ->count();

            $averageOnTimeArrival = $totalJobsCompleted > 0
                ? round(($onTimeBookings / $totalJobsCompleted) * 100, 2)
                : 0;

            // Repeated customers: users who booked >1 time with same partner
            $repeatedCustomers = Booking::whereHas('requests', function ($q) use ($partnerId) {
                $q->where('partner_id', $partnerId)
                    ->where('status', 'accepted');
            })
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            return response()->json([
                'success'                  => true,
                'average_rating'           => $averageRating,
                'total_jobs_completed'     => $totalJobsCompleted,
                'average_on_time_arrival'  => $averageOnTimeArrival,
                'repeated_customers_count' => $repeatedCustomers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching partner stats.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function maidAverageRating()
    {
        try {
            // Get the maid service (id = 5)
            $service = Service::with('partners')->find(5);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maid service not found.'
                ], 404);
            }

            // Get all partner profiles that offer this service
            $partners = $service->partners()->whereNotNull('average_rating')->get();

            if ($partners->count() === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No rated partners found for maid service.',
                    'average_rating' => 0,
                    'partners_count' => 0,
                ]);
            }

            // Calculate overall average rating
            $averageRating = round($partners->avg('average_rating'), 2);

            return response()->json([
                'success' => true,
                'service' => $service->name,
                'average_rating' => $averageRating,
                'partners_count' => $partners->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating maid service average rating.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
