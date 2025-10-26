<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class InitialDiscountSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'initial_discount'],
            [
                'value' => '10', // Default discount percentage
                'type' => 'percentage',
                'description' => 'Initial discount percentage applied to new users or first bookings.'
            ]
        );
    }
}
