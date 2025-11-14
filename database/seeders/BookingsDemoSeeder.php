<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\MaidPricing;
use App\Models\Service;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;

class BookingsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $service = Service::first();
        if (!$service) {
            return; // services seeded by ServiceSeeder
        }

        $users = User::where('role', 'user')->take(3)->get();
        foreach ($users as $index => $user) {
            $address = UserAddress::where('user_id', $user->id)->first();
            if (!$address) {
                continue;
            }

            $amount = 300 + $index * 50;
            $tax = round($amount * 0.05, 2);
            $total = $amount + $tax;

            $booking = Booking::create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'address_id' => $address->id,
                'schedule_date' => now()->addDays($index + 1)->toDateString(),
                'schedule_time' => '10:00-12:00',
                'payment_method' => 'cod',
                'amount' => $amount,
                'tax' => $tax,
                'total_amount' => $total,
                'initial_discount_applied' => $index % 2 === 0,
                'status' => 'confirmed',
                'service_time' => 120,
            ]);

            // Optional: for maid package link if exists
            if (class_exists(MaidPricing::class)) {
                $pkg = MaidPricing::first();
                if ($pkg) {
                    $booking->service_time = $pkg->time;
                    $booking->save();
                }
            }
        }
    }
}


