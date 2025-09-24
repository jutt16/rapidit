<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use Illuminate\Http\Request;

class BookingRequestController extends Controller
{
    /**
     * Get all booking requests for the authenticated user/partner
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user(); // from Sanctum

            $requests = BookingRequest::with([
                'booking.user',
                'booking.service',
                'booking.address',
                'partner.partnerprofile'
            ])
                ->when($user->role === 'partner', function ($q) use ($user) {
                    // Partner → only their requests
                    $q->where('partner_id', $user->id);
                })
                ->when($user->role === 'user', function ($q) use ($user) {
                    // Normal user → only their bookings’ requests
                    $q->whereHas('booking', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
                })
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $requests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'    => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Accept a booking request (partner only)
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();

        $bookingRequest = BookingRequest::with('booking')
            ->where('partner_id', $user->id) // only if belongs to this partner
            ->findOrFail($id);

        if ($bookingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be accepted.',
            ], 422);
        }

        // Mark this one accepted
        $bookingRequest->update(['status' => 'accepted']);

        // Expire all other requests for this booking
        BookingRequest::where('booking_id', $bookingRequest->booking_id)
            ->where('id', '!=', $bookingRequest->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        Booking::findOrFail($bookingRequest->booking_id)
            ->update(['status' => 'accepted']);

        return response()->json([
            'success' => true,
            'message' => 'Booking request accepted.',
            'data'    => $bookingRequest->load('booking', 'partner'),
        ]);
    }

    /**
     * Reject a booking request (partner only)
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user();

        $bookingRequest = BookingRequest::with('booking')
            ->where('partner_id', $user->id) // only if belongs to this partner
            ->findOrFail($id);

        if ($bookingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be rejected.',
            ], 422);
        }

        // Mark this one rejected
        $bookingRequest->update(['status' => 'rejected']);

        // Expire all other requests for this booking
        BookingRequest::where('booking_id', $bookingRequest->booking_id)
            ->where('id', '!=', $bookingRequest->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        return response()->json([
            'success' => true,
            'message' => 'Booking request rejected.',
            'data'    => $bookingRequest->load('booking', 'partner'),
        ]);
    }
}
