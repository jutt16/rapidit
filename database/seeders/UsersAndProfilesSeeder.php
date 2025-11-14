<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserAddress;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersAndProfilesSeeder extends Seeder
{
    public function run(): void
    {
        // Admin (if not already created in DatabaseSeeder)
        User::updateOrCreate(
            ['phone' => '91000000000'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'phone_verified' => true,
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // Customers
        for ($i = 1; $i <= 5; $i++) {
            $user = User::updateOrCreate(
                ['phone' => '9100000000' . $i],
                [
                    'name' => 'Customer ' . $i,
                    'password' => Hash::make('password'),
                    'phone_verified' => true,
                    'role' => 'user',
                    'status' => 'active',
                ]
            );

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'email' => 'customer' . $i . '@example.com',
                ]
            );

            UserAddress::updateOrCreate(
                ['user_id' => $user->id, 'label' => 'Home'],
                [
                    'addressLine' => 'Street ' . $i,
                    'city' => 'City',
                    'state' => 'ST',
                    'postalCode' => '1000' . $i,
                    'latitude' => 18.5204 + $i / 1000,
                    'longitude' => 73.8567 + $i / 1000,
                ]
            );

            Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
            $user->creditWallet(1000, 'Initial test credit');
        }

        // Partners
        for ($i = 1; $i <= 5; $i++) {
            $partner = User::updateOrCreate(
                ['phone' => '9200000000' . $i],
                [
                    'name' => 'Partner ' . $i,
                    'password' => Hash::make('password'),
                    'phone_verified' => true,
                    'role' => 'partner',
                    'status' => 'active',
                    'partner_status' => 'approved',
                ]
            );

            // Basic profile and availability
            \App\Models\PartnerProfile::updateOrCreate(
                ['user_id' => $partner->id],
                [
                    'full_name' => $partner->name,
                    'gender' => 'female',
                    'languages' => ['en', 'hi'],
                    'years_of_experience' => rand(1, 8),
                    'average_rating' => 4.5,
                    'reviews_count' => rand(5, 50),
                    'latitude' => 18.52 + $i / 1000,
                    'longitude' => 73.85 + $i / 1000,
                ]
            );

            \App\Models\PartnerAvailability::updateOrCreate(
                ['partner_id' => $partner->id],
                [
                    'is_available' => true,
                    'status' => 'available',
                    'start_time' => '08:00',
                    'end_time' => '20:00',
                    'blocked_dates' => [],
                ]
            );

            Wallet::firstOrCreate(['user_id' => $partner->id], ['balance' => 0]);
            $partner->creditWallet(2500, 'Initial partner earnings');
        }
    }
}


