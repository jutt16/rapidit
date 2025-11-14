<?php

namespace Database\Seeders;

use App\Models\BankingDetail;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WithdrawalsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $partners = User::where('role', 'partner')->take(3)->get();
        foreach ($partners as $i => $partner) {
            $bank = BankingDetail::where('user_id', $partner->id)->first();
            if (!$bank) continue;

            $amount = 500 + $i * 200;
            $fee = 10;

            // Ensure wallet exists and has funds
            $partner->creditWallet($amount + $fee + 100, 'Seed top-up for withdrawal');

            // Mimic reservation by debiting (like API flow)
            try {
                $partner->debitWallet($amount + $fee, 'Withdrawal request (seeded)');
            } catch (\Throwable $e) {
                // skip if could not debit
                continue;
            }

            Withdrawal::create([
                'user_id' => $partner->id,
                'banking_detail_id' => $bank->id,
                'amount' => $amount,
                'fee' => $fee,
                'currency' => 'INR',
                'status' => $i % 2 === 0 ? 'pending' : 'completed',
                'reference' => $i % 2 === 0 ? Str::uuid() : 'SEED-TX-' . strtoupper(Str::random(6)),
                'utr' => $i % 2 === 0 ? null : 'SEED-UTR-' . strtoupper(Str::random(6)),
                'processed_by' => null,
                'processed_at' => $i % 2 === 0 ? null : now(),
            ]);
        }
    }
}


