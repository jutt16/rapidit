<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('query');

        if (empty($query)) {
            return redirect()->route('admin.dashboard');
        }

        $results = [];

        // Search Users
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        if ($users->count() > 0) {
            $results['users'] = $users;
        }

        // Search Partners
        $partners = User::where('role', 'partner')
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        if ($partners->count() > 0) {
            $results['partners'] = $partners;
        }

        // Search Bookings (by ID, user name, or partner name)
        $bookings = Booking::with(['user', 'service'])
            ->whereHas('user', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhere('id', $query)
            ->limit(10)
            ->get();

        if ($bookings->count() > 0) {
            $results['bookings'] = $bookings;
        }

        // Search Withdrawals (by ID or user name)
        $withdrawals = \App\Models\Withdrawal::with('user')
            ->whereHas('user', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhere('id', $query)
            ->limit(10)
            ->get();

        if ($withdrawals->count() > 0) {
            $results['withdrawals'] = $withdrawals;
        }

        return view('admin.search.index', compact('results', 'query'));
    }
}

