<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingStatsController extends Controller
{
    /**
     * Get booking counts for last 24 hours
     */
    public function last24HoursCount(Request $request)
    {
        $user = $request->user();
        $last24Hours = Carbon::now()->subHours(24);

        // Total bookings in last 24 hours for the user
        $totalCount = Booking::where('user_id', $user->id)
            ->where('created_at', '>=', $last24Hours)
            ->count();

        // Count by status
        $countByStatus = Booking::where('user_id', $user->id)
            ->where('created_at', '>=', $last24Hours)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $totalCount,
                'by_status' => $countByStatus,
                'period' => 'last_24_hours',
                'from' => $last24Hours->toDateTimeString(),
                'to' => Carbon::now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Get partner booking counts for last 24 hours
     * (For partners to see their accepted bookings)
     */
    public function partnerLast24HoursCount(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'partner') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for partners',
            ], 403);
        }

        $last24Hours = Carbon::now()->subHours(24);

        // Get bookings where this partner was assigned through booking_requests
        $totalCount = Booking::whereHas('requests', function ($query) use ($user) {
            $query->where('partner_id', $user->id)
                  ->where('status', 'accepted');
        })
        ->where('created_at', '>=', $last24Hours)
        ->count();

        // Count by booking status
        $countByStatus = Booking::whereHas('requests', function ($query) use ($user) {
            $query->where('partner_id', $user->id)
                  ->where('status', 'accepted');
        })
        ->where('created_at', '>=', $last24Hours)
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->get()
        ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $totalCount,
                'by_status' => $countByStatus,
                'period' => 'last_24_hours',
                'from' => $last24Hours->toDateTimeString(),
                'to' => Carbon::now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Get system-wide booking counts for last 24 hours (Admin only)
     */
    public function systemLast24HoursCount(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $last24Hours = Carbon::now()->subHours(24);

        // Total bookings in last 24 hours
        $totalCount = Booking::where('created_at', '>=', $last24Hours)->count();

        // Count by status
        $countByStatus = Booking::where('created_at', '>=', $last24Hours)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Count by service
        $countByService = Booking::where('created_at', '>=', $last24Hours)
            ->with('service:id,name')
            ->get()
            ->groupBy('service.name')
            ->map(function ($group) {
                return $group->count();
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $totalCount,
                'by_status' => $countByStatus,
                'by_service' => $countByService,
                'period' => 'last_24_hours',
                'from' => $last24Hours->toDateTimeString(),
                'to' => Carbon::now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Get overall booking counts in last 24 hours (Public - no auth required)
     * Returns system-wide statistics
     */
    public function overallLast24Hours()
    {
        $last24Hours = Carbon::now()->subHours(24);

        // Total bookings in last 24 hours
        $totalCount = Booking::where('created_at', '>=', $last24Hours)->count();

        // Count by status
        $countByStatus = Booking::where('created_at', '>=', $last24Hours)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Pending bookings (waiting for partner assignment)
        $pendingCount = Booking::where('created_at', '>=', $last24Hours)
            ->where('status', 'pending')
            ->count();

        // Confirmed bookings
        $confirmedCount = Booking::where('created_at', '>=', $last24Hours)
            ->where('status', 'confirmed')
            ->count();

        // Completed bookings
        $completedCount = Booking::where('created_at', '>=', $last24Hours)
            ->where('status', 'completed')
            ->count();

        // Cancelled bookings
        $cancelledCount = Booking::where('created_at', '>=', $last24Hours)
            ->where('status', 'cancelled')
            ->count();

        $averageRating = Review::where('reviewer_type', 'partner')->avg('rating');

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $totalCount,
                'pending_count' => $pendingCount,
                'confirmed_count' => $confirmedCount,
                'completed_count' => $completedCount,
                'cancelled_count' => $cancelledCount,
                'by_status' => $countByStatus,
                'period' => 'last_24_hours',
                'from' => $last24Hours->toDateTimeString(),
                'to' => Carbon::now()->toDateTimeString(),
                'average_partner_rating' => $averageRating,
            ],
        ]);
    }

    /**
     * Get nearby bookings count based on latitude and longitude
     * Uses Haversine formula to calculate distance
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nearbyBookingsCount(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100', // radius in kilometers
            'hours' => 'nullable|integer|min:1|max:168', // hours to look back (default 24, max 7 days)
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 5; // default 5km radius
        $hours = $request->hours ?? 24; // default last 24 hours

        $timeThreshold = Carbon::now()->subHours($hours);

        // Haversine formula to calculate distance
        // (6371 is Earth's radius in kilometers)
        $haversine = "(6371 * acos(cos(radians(?)) 
                     * cos(radians(user_addresses.latitude)) 
                     * cos(radians(user_addresses.longitude) - radians(?)) 
                     + sin(radians(?)) 
                     * sin(radians(user_addresses.latitude))))";

        // Get bookings within radius
        $nearbyBookings = Booking::join('user_addresses', 'bookings.address_id', '=', 'user_addresses.id')
            ->whereNotNull('user_addresses.latitude')
            ->whereNotNull('user_addresses.longitude')
            ->where('bookings.created_at', '>=', $timeThreshold)
            ->selectRaw("bookings.*, 
                {$haversine} AS distance", 
                [$latitude, $longitude, $latitude]
            )
            ->having('distance', '<=', $radius)
            ->get();

        // Count by status
        $countByStatus = $nearbyBookings->groupBy('status')->map(function ($group) {
            return $group->count();
        });

        // Get booking details (optional, for more info)
        $bookingDetails = $nearbyBookings->map(function ($booking) {
            return [
                'booking_id' => $booking->id,
                'status' => $booking->status,
                'distance_km' => round($booking->distance, 2),
                'schedule_date' => $booking->schedule_date,
                'schedule_time' => $booking->schedule_time,
                'created_at' => $booking->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_nearby_count' => $nearbyBookings->count(),
                'search_center' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
                'search_radius_km' => $radius,
                'time_period_hours' => $hours,
                'from' => $timeThreshold->toDateTimeString(),
                'to' => Carbon::now()->toDateTimeString(),
                'count_by_status' => $countByStatus,
                'bookings' => $bookingDetails,
            ],
        ]);
    }
}

