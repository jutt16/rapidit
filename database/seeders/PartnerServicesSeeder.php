<?php

namespace Database\Seeders;

use App\Models\PartnerProfile;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PartnerServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::take(3)->get();
        if ($services->isEmpty()) return;

        $partners = PartnerProfile::all();
        foreach ($partners as $profile) {
            $attach = [];
            foreach ($services as $srv) {
                $attach[$srv->id] = ['own_tools_available' => (bool) random_int(0, 1)];
            }
            $profile->services()->syncWithoutDetaching($attach);
        }
    }
}


