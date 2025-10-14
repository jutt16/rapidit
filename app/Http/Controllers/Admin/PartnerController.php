<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    // List all partners with optional status filter
    public function index(Request $request)
    {
        $query = User::where('role', 'partner');

        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('partner_status', $request->status);
        }

        $partners = $query->with('partnerProfile.services.category')->paginate(15);

        return view('admin.partners.index', compact('partners'));
    }

    // Show details of a partner
    public function show(User $user)
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        $user->load('partnerProfile.services.category');

        return view('admin.partners.show', compact('user'));
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

        return redirect()->back()->with('success', 'Partner status updated successfully.');
    }
}
