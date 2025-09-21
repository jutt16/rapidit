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
    }
}
