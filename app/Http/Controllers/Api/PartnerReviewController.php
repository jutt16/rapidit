<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class PartnerReviewController extends Controller
{
    /**
     * GET /api/partner/reviews
     * Authenticated partner can fetch their reviews with booking details
     */
    public function index(Request $request)
    {
        $partnerId = $request->user()->id;

        $reviews = Review::where('partner_id', $partnerId)
            ->where('status', 'approved')
            ->with([
                'user:id,name', // reviewer info
                'booking:id,user_id,status,schedule_date,address_id' // booking details
            ])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }
}
