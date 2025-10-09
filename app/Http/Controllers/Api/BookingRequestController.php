<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
        try {
            $user = $request->user();

            $bookingRequest = BookingRequest::with('booking')
                ->where('partner_id', $user->id)
                ->findOrFail($id);

            if ($bookingRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be accepted.',
                ], 422);
            }

            // Ensure wallet exists
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            // ✅ Check partner wallet balance before accepting
            if ($wallet->balance < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your wallet balance is negative. Please recharge before accepting new bookings.',
                    'current_balance' => $wallet->balance,
                ], 402); // 402 Payment Required
            }

            // ✅ Mark this one accepted
            $bookingRequest->update(['status' => 'accepted']);

            // ✅ Expire all other pending requests
            BookingRequest::where('booking_id', $bookingRequest->booking_id)
                ->where('id', '!=', $bookingRequest->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            // ✅ Update booking status
            Booking::findOrFail($bookingRequest->booking_id)
                ->update(['status' => 'accepted', 'partner_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Booking request accepted successfully.',
                'data' => $bookingRequest->load('booking', 'partner'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Reject a booking request (partner only)
     */
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

        // Check if there are other requests for this booking still pending or accepted
        $hasActiveRequests = BookingRequest::where('booking_id', $bookingRequest->booking_id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if (! $hasActiveRequests) {
            // No active requests → reject booking itself
            $bookingRequest->booking->update(['status' => 'rejected']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking request rejected.',
            'data'    => $bookingRequest->load('booking', 'partner'),
        ]);
    }

    /**
     * Mark arrival time for a booking using its booking_id.
     */
    public function markArrival(Request $request, $booking_id)
    {
        // Retrieve booking request by booking_id
        $bookingRequest = BookingRequest::where('booking_id', $booking_id)->first();

        if (!$bookingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Booking request not found for this booking ID.',
            ], 404);
        }

        // Optional: restrict only the assigned partner to mark arrival
        if (auth()->id() !== $bookingRequest->partner_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Update arrival time
        $bookingRequest->arrival_time = $request->input('arrival_time', Carbon::now());
        $bookingRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Arrival time marked successfully.',
            'data' => [
                'booking_id' => $bookingRequest->booking_id,
                'arrival_time' => $bookingRequest->arrival_time->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
