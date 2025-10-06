<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // ✅ Ensure wallet exists for this user
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // ✅ Get all transactions (latest first)
        $transactions = $wallet->transactions()
            ->latest()
            ->get(['id', 'type', 'amount', 'description', 'created_at']);

        // ✅ Calculate earnings (only credit transactions)
        $weeklyEarnings = $wallet->transactions()
            ->where('type', 'credit')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('amount');

        $monthlyEarnings = $wallet->transactions()
            ->where('type', 'credit')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('amount');

        $totalEarnings = $wallet->transactions()
            ->where('type', 'credit')
            ->sum('amount');

        // ✅ Response
        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet->balance,
                'weekly_earnings' => $weeklyEarnings,
                'monthly_earnings' => $monthlyEarnings,
                'total_earnings' => $totalEarnings,
                'transactions' => $transactions,
            ]
        ]);
    }
}
