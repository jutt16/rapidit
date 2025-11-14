<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalPartners = User::where('role', 'partner')->count();
        $totalCustomers = User::where('role', 'user')->count();

        $totalServices = Service::count();

        $totalBookings = Booking::count();
        $completedBookings = Booking::where('status', 'completed')->count();

        $totalRevenue = (float) BookingPayment::where('status', 'paid')->sum('amount');

        // Platform profit: sum of booking amount * commission_pct for bookings with a paid payment
        $commissionSum = (float) DB::table('bookings')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->join('booking_payments', 'booking_payments.booking_id', '=', 'bookings.id')
            ->where('booking_payments.status', 'paid')
            ->sum(DB::raw('(bookings.amount * (services.commission_pct/100))'));

        $platformProfit = round($commissionSum, 2);

        $pendingWithdrawals = Withdrawal::whereIn('status', ['pending','processing'])->count();
        $completedWithdrawals = Withdrawal::where('status', 'completed')->count();
        $totalPayouts = (float) Withdrawal::where('status', 'completed')->sum('amount');

        $recentBookings = Booking::with('user','service')
            ->latest()->limit(10)->get();
        $recentWithdrawals = Withdrawal::with('user')
            ->latest()->limit(10)->get();

        // Charts: last 30 days timeseries
        $fromDate = now()->subDays(29)->startOfDay();

        // Bookings per day
        $bookingsByDay = DB::table('bookings')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->where('created_at', '>=', $fromDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->pluck('c', 'd')
            ->toArray();

        // Revenue per day (paid payments)
        $revenueByDay = DB::table('booking_payments')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(amount) as s'))
            ->where('status', 'paid')
            ->where('created_at', '>=', $fromDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('d')
            ->pluck('s', 'd')
            ->toArray();

        // Payouts per day (completed withdrawals)
        $payoutsByDay = DB::table('withdrawals')
            ->select(DB::raw('DATE(processed_at) as d'), DB::raw('SUM(amount) as s'))
            ->where('status', 'completed')
            ->whereNotNull('processed_at')
            ->where('processed_at', '>=', $fromDate)
            ->groupBy(DB::raw('DATE(processed_at)'))
            ->orderBy('d')
            ->pluck('s', 'd')
            ->toArray();

        // Bookings by service (top 6 in last 30 days)
        $serviceDistribution = DB::table('bookings')
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->select('services.name as name', DB::raw('COUNT(*) as c'))
            ->where('bookings.created_at', '>=', $fromDate)
            ->groupBy('services.name')
            ->orderByDesc('c')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalPartners',
            'totalCustomers',
            'totalServices',
            'totalBookings',
            'completedBookings',
            'totalRevenue',
            'platformProfit',
            'pendingWithdrawals',
            'completedWithdrawals',
            'totalPayouts',
            'recentBookings',
            'recentWithdrawals',
            'bookingsByDay',
            'revenueByDay',
            'payoutsByDay',
            'serviceDistribution'
        ));
    }
}
