<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\PartnerProfile;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Create a review for a booking
     * POST /api/bookings/{booking}/reviews
     */
    public function store(Request $request, Booking $booking)
    {
        try {
            $user = $request->user();

            $data = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
                'reviewer_type' => 'required|in:user,partner'
            ]);

            // Ensure booking is completed
            if (!in_array($booking->status, ['completed', 'completed_by_partner'])) {
                return response()->json(['success' => false, 'message' => 'You can leave a review only after booking is completed'], 422);
            }

            // If reviewer is user → review partner
            if ($data['reviewer_type'] === 'user') {
                if ($booking->user_id !== $user->id) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }

                // prevent duplicate
                if (Review::where('booking_id', $booking->id)->where('reviewer_type', 'user')->exists()) {
                    return response()->json(['success' => false, 'message' => 'User review already submitted for this booking'], 409);
                }

                $acceptedRequest = $booking->requests()->where('status', 'accepted')->first();
                $partnerId = $acceptedRequest->partner_id ?? null;

                $review = Review::create([
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'partner_id' => $partnerId,
                    'reviewer_type' => 'user',
                    'rating' => $data['rating'],
                    'comment' => $data['comment'],
                    'status' => 'approved',
                ]);

                if ($partnerId) {
                    $this->recalculatePartnerRating($partnerId);
                }
            }

            // If reviewer is partner → review user
            else {
                $acceptedRequest = $booking->requests()->where('status', 'accepted')->first();
                if (!$acceptedRequest || $acceptedRequest->partner_id !== $user->id) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }

                if (Review::where('booking_id', $booking->id)->where('reviewer_type', 'partner')->exists()) {
                    return response()->json(['success' => false, 'message' => 'Partner review already submitted for this booking'], 409);
                }

                $review = Review::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'partner_id' => $user->id,
                    'reviewer_type' => 'partner',
                    'rating' => $data['rating'],
                    'comment' => $data['comment'],
                    'status' => 'approved',
                ]);

                $this->recalculateUserRating($booking->user_id);
            }

            return response()->json(['success' => true, 'message' => 'Review submitted', 'data' => $review], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function recalculateUserRating($userId)
    {
        $stats = Review::where('user_id', $userId)
            ->where('reviewer_type', 'partner')
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as cnt, COALESCE(ROUND(AVG(rating),2),0) as avg')
            ->first();

        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->update([
                'average_rating' => $stats->avg ?? 0,
                'reviews_count' => $stats->cnt ?? 0,
            ]);
        }
    }


    /**
     * Update review (owner only, if you allow)
     * PUT /api/bookings/{booking}/reviews/{id}
     */
    public function update(Request $request, Booking $booking, $id)
    {
        $user = $request->user();
        $review = Review::findOrFail($id);

        if ($review->user_id !== $user->id || $review->booking_id !== $booking->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($review, $data) {
            $review->update($data);
            if ($review->partner_id) {
                // update partner aggregates
                app(\App\Http\Controllers\Api\ReviewController::class)->recalculatePartnerRating($review->partner_id);
            }
        });

        return response()->json(['success' => true, 'message' => 'Review updated', 'data' => $review]);
    }

    /**
     * List reviews for a partner
     * GET /api/partners/{partner}/reviews
     */
    public function partnerReviews($partnerId)
    {
        $reviews = Review::where('partner_id', $partnerId)
            ->where('status', 'approved')
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $reviews]);
    }

    /**
     * Recalculate partner aggregated rating and store to partner_profiles
     */
    public function recalculatePartnerRating($partnerId)
    {
        $stats = Review::where('partner_id', $partnerId)
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as cnt, COALESCE(ROUND(AVG(rating),2),0) as avg')
            ->first();

        $profile = PartnerProfile::where('user_id', $partnerId)->first();
        if ($profile) {
            $profile->update([
                'average_rating' => $stats->avg ?? 0,
                'reviews_count' => $stats->cnt ?? 0,
            ]);
        }
    }
}
