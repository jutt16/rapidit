<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    // List all partners with optional status filter
    public function index(Request $request)
    {
        $query = User::where('role', 'partner');

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('partner_status', $request->status);
        }

        // 🔍 Search by name or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('partnerProfile', function ($subQuery) use ($search) {
                        $subQuery->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $partners = $query
            ->with(['partnerProfile.services.category', 'addresses'])
            ->latest('created_at')
            ->paginate(15);

        return view('admin.partners.index', compact('partners'));
    }

    // Show details of a partner
    public function show(User $user)
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        $user->load(['partnerProfile.services.category', 'addresses', 'wallet']);

        // Lifetime booking request stats
        $requestStatusCounts = BookingRequest::where('partner_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalRequests = array_sum($requestStatusCounts);

        // Booking outcomes for requests accepted by this partner
        $bookingStatusCounts = Booking::whereHas('requests', function ($query) use ($user) {
                $query->where('partner_id', $user->id)
                    ->where('status', 'accepted');
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Earnings (wallet credits)
        $earnings = [
            'lifetime' => 0.0,
            'mtd' => 0.0,
            'ytd' => 0.0,
        ];

        $walletId = optional($user->wallet)->id;
        if ($walletId) {
            $now = Carbon::now();
            $earnings['lifetime'] = (float) WalletTransaction::where('wallet_id', $walletId)
                ->where('type', 'credit')
                ->sum('amount');

            $earnings['mtd'] = (float) WalletTransaction::where('wallet_id', $walletId)
                ->where('type', 'credit')
                ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now])
                ->sum('amount');

            $earnings['ytd'] = (float) WalletTransaction::where('wallet_id', $walletId)
                ->where('type', 'credit')
                ->whereBetween('created_at', [$now->copy()->startOfYear(), $now])
                ->sum('amount');
        }

        $partnerStats = [
            'total_requests' => $totalRequests,
            'accepted_requests' => $requestStatusCounts['accepted'] ?? 0,
            'rejected_requests' => $requestStatusCounts['rejected'] ?? 0,
            'cancelled_requests' => $requestStatusCounts['cancelled'] ?? 0,
            'completed_jobs' => $bookingStatusCounts['completed'] ?? 0,
            'cancelled_jobs' => $bookingStatusCounts['cancelled'] ?? 0,
            'accepted_jobs' => $bookingStatusCounts['accepted'] ?? 0,
            'earnings' => $earnings,
        ];

        return view('admin.partners.show', compact('user', 'partnerStats'));
    }

    // Update partner_status
    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'partner_status' => 'required|in:pending,approved,rejected',
            'rejection_notes' => 'nullable|string|max:2000',
        ]);

        // If rejected, make sure note is provided
        if ($request->partner_status === 'rejected' && empty($request->rejection_notes)) {
            return back()->withErrors(['rejection_notes' => 'Please provide a reason for rejection.'])->withInput();
        }

        $user->update([
            'partner_status' => $request->partner_status,
            'rejection_notes' => $request->partner_status === 'rejected' ? $request->rejection_notes : null,
        ]);

        // Send notification to partner
        if ($request->partner_status === 'approved') {
            app(\App\Services\FcmService::class)->sendToUser(
                $user,
                'Partner Application Approved',
                'Congratulations! Your partner application has been approved.',
                [
                    'type' => 'partner_approved',
                    'user_id' => (string)$user->id,
                ]
            );
        } elseif ($request->partner_status === 'rejected') {
            app(\App\Services\FcmService::class)->sendToUser(
                $user,
                'Partner Application Rejected',
                $request->rejection_notes ?? 'Your partner application has been rejected.',
                [
                    'type' => 'partner_rejected',
                    'user_id' => (string)$user->id,
                ]
            );
        }

        return redirect()->back()->with('success', 'Partner status updated successfully.');
    }

    // Export partners to CSV
    public function export(Request $request)
    {
        $query = User::where('role', 'partner')
            ->with(['partnerProfile', 'wallet']);

        // Apply filters if any
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('partner_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('partnerProfile', function ($subQuery) use ($search) {
                        $subQuery->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $partners = $query->latest()->get();

        $fileName = 'partners_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function() use ($partners) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Phone',
                'Status',
                'Partner Status',
                'Experience (Years)',
                'Rating',
                'Total Reviews',
                'Wallet Balance',
                'Bio',
                'Rejection Notes',
                'Registered At',
                'Last Updated'
            ]);

            // CSV rows
            foreach ($partners as $partner) {
                fputcsv($file, [
                    $partner->id,
                    $partner->name,
                    $partner->phone,
                    $partner->status ? 'Active' : 'Inactive',
                    ucfirst($partner->partner_status ?? 'N/A'),
                    $partner->partnerProfile->experience ?? 'N/A',
                    $partner->partnerProfile->rating ?? '0.0',
                    $partner->partnerProfile->total_reviews ?? '0',
                    $partner->wallet ? $partner->wallet->balance : '0.00',
                    $partner->partnerProfile->bio ?? 'N/A',
                    $partner->rejection_notes ?? 'N/A',
                    $partner->created_at,
                    $partner->updated_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
