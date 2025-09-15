<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PartnerProfile;
use Illuminate\Http\Request;

class PartnerStatsController extends Controller
{
    public function stats(Request $request)
    {
        $partnerId = $request->query('partner_id');

        if (!$partnerId) {
            return response()->json(['success' => false, 'message' => 'partner_id is required'], 422);
        }

        // Get partner profile
        $profile = PartnerProfile::where('user_id', $partnerId)->first();

        // Average rating (already stored, fallback to 0)
        $averageRating = $profile->average_rating ?? 0;

        // Completed jobs count
        $totalJobsCompleted = Booking::whereHas('requests', function ($q) use ($partnerId) {
            $q->where('partner_id', $partnerId)
                ->where('status', 'accepted');
        })
            ->where('status', 'completed')
            ->count();

        // On-time arrival (example: compare actual_start vs schedule_time)
        $onTimeBookings = Booking::whereHas('requests', function ($q) use ($partnerId) {
            $q->where('partner_id', $partnerId)
                ->where('status', 'accepted');
        })
            ->where('status', 'completed')
            ->whereColumn('actual_start_time', '<=', 'schedule_time')
            ->count();

        $averageOnTimeArrival = $totalJobsCompleted > 0
            ? round(($onTimeBookings / $totalJobsCompleted) * 100, 2)
            : 0;

        // Repeated customers = count of users who booked >1 time with this partner
        $repeatedCustomers = Booking::whereHas('requests', function ($q) use ($partnerId) {
            $q->where('partner_id', $partnerId)
                ->where('status', 'accepted');
        })
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return response()->json([
            'average_rating'        => $averageRating,
            'total_jobs_completed'  => $totalJobsCompleted,
            'average_on_time_arrival' => $averageOnTimeArrival,
            'repeated_customers_count' => $repeatedCustomers,
        ]);
    }
}
