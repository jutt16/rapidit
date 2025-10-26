<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\StaticPagesSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '923001234567'], // unique identifier
            [
                'name' => 'Super Admin',
                'phone' => '91000000000',
                'password' => Hash::make('admin123'), // default password
                'phone_verified' => true,
                'role' => 'admin',
                'status' => 'active',
                'fcm_token' => null,
            ]
        );
        $this->call([
            CategorySeeder::class,
            ServiceSeeder::class,
            StaticPagesSeeder::class,
            SettingsSeeder::class,
            InitialDiscountSettingSeeder::class,
        ]);
    }
}
