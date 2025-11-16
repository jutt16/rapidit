<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        Setting::updateOrCreate(
            ['key' => 'provider_radius'],
            ['value' => '10', 'type' => 'float', 'description' => 'Default provider search radius in km']
        );

        Setting::updateOrCreate(
            ['key' => 'initial_discount'],
            ['value' => '5', 'type' => 'float', 'description' => 'Default initial discount percentage']
        );

        Setting::updateOrCreate(
            ['key' => 'upto_initial_discount'],
            ['value' => '0', 'type' => 'float', 'description' => 'Up to initial discount amount/percentage']
        );

        Setting::updateOrCreate(
            ['key' => 'app_timezone'],
            ['value' => config('app.timezone'), 'type' => 'string', 'description' => 'Application timezone']
        );
    }
}
