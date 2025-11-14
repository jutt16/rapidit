<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class WalletTopUpSeeder extends Seeder
{
    public function run(): void
    {
        // Top-up all users with small balances and create a transaction entry
        $users = User::all();
        foreach ($users as $user) {
            try {
                $user->creditWallet(200, 'General seed credit');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
}


