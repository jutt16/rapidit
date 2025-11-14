<?php

namespace Database\Seeders;

use App\Models\BankingDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class BankingDetailsSeeder extends Seeder
{
    public function run(): void
    {
        // Add one banking detail for each partner
        $partners = User::where('role', 'partner')->get();
        foreach ($partners as $partner) {
            BankingDetail::updateOrCreate(
                ['user_id' => $partner->id, 'account_number' => '9999999999' . $partner->id],
                [
                    'bank_name' => 'HDFC Bank',
                    'account_holder_name' => $partner->name,
                    'ifsc' => 'HDFC0000001', // Razorpay test IFSC
                    'branch' => 'Main Branch',
                    'currency' => 'INR',
                    'is_default' => true,
                    'status' => 'verified',
                ]
            );
        }
    }
}


