<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerProfile;
use App\Models\MaidPricing;
use Illuminate\Http\Request;

class PartnerLocationController extends Controller
{
    /**
     * Get count of experts (partners) near a specific location
     * Uses Haversine formula to calculate distance
     * 
     * @param Request $request with latitude and longitude
     * @return \Illuminate\Http\JsonResponse
     */
    public function expertsNearYouCount(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1', // radius in kilometers, default 10km
            'service_id' => 'nullable|integer|exists:services,id', // optional filter by service
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 10; // default 10km radius

        // Haversine formula to calculate distance
        // Returns distance in kilometers
        $query = PartnerProfile::selectRaw("
            partner_profiles.*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$latitude, $longitude, $latitude])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->having('distance', '<=', $radius);

        // Filter by service if provided
        if ($request->has('service_id')) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('service_id', $request->service_id);
            });
        }

        // Only include active partners
        $query->whereHas('user', function ($q) {
            $q->where('partner_status', 'approved')
              ->where('status', 'active');
        });

        $count = $query->count();

        return response()->json([
            'success' => true,
            'data' => [
                'experts_count' => $count,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_km' => $radius,
                'service_id' => $request->service_id,
            ],
        ]);
    }

    /**
     * Get the minimum (starting) price for maid service from nearest available maids
     * 
     * @param Request $request with latitude and longitude
     * @return \Illuminate\Http\JsonResponse
     */
    public function nearestMaidStartingPrice(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1', // radius in kilometers, default 20km
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 20; // default 20km radius

        // Get maid service ID (assuming service name is 'maid' or 'Maid')
        $maidService = \App\Models\Service::where('name', 'like', '%maid%')->first();

        if (!$maidService) {
            return response()->json([
                'success' => false,
                'message' => 'Maid service not found',
            ], 404);
        }

        // Find nearest maids within radius
        $nearestMaids = PartnerProfile::selectRaw("
            partner_profiles.*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$latitude, $longitude, $latitude])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->whereHas('services', function ($q) use ($maidService) {
            $q->where('service_id', $maidService->id);
        })
        ->whereHas('user', function ($q) {
            $q->where('partner_status', 'approved')
              ->where('status', 'active');
        })
        ->having('distance', '<=', $radius)
        ->orderBy('distance', 'asc')
        ->exists();

        if (!$nearestMaids) {
            return response()->json([
                'success' => false,
                'message' => 'No maids found within the specified radius',
                'data' => [
                    'starting_price' => null,
                    'radius_km' => $radius,
                ],
            ], 404);
        }

        // Get the minimum price from MaidPricing table
        $minimumPrice = MaidPricing::orderBy('price', 'asc')->first();

        if (!$minimumPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Maid pricing not configured',
            ], 404);
        }

        // Calculate final price after discount
        $discountAmount = ($minimumPrice->price * $minimumPrice->discount) / 100;
        $finalPrice = $minimumPrice->price - $discountAmount;

        return response()->json([
            'success' => true,
            'data' => [
                'starting_price' => $finalPrice,
                'original_price' => $minimumPrice->price,
                'discount_percentage' => $minimumPrice->discount,
                'discount_amount' => $discountAmount,
                'service_time_minutes' => $minimumPrice->time,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_km' => $radius,
                'maids_available' => true,
            ],
        ]);
    }

    /**
     * Get list of experts near a location with details
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function expertsNearYouList(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1',
            'service_id' => 'nullable|integer|exists:services,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 10;
        $limit = $request->limit ?? 20;

        $query = PartnerProfile::selectRaw("
            partner_profiles.*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$latitude, $longitude, $latitude])
        ->with(['user:id,name,phone,status', 'services'])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->having('distance', '<=', $radius);

        if ($request->has('service_id')) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('service_id', $request->service_id);
            });
        }

        $query->whereHas('user', function ($q) {
            $q->where('partner_status', 'approved')
              ->where('status', 'active');
        });

        $experts = $query->orderBy('distance', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $experts,
            'count' => $experts->count(),
            'search_params' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_km' => $radius,
                'service_id' => $request->service_id,
            ],
        ]);
    }
}

