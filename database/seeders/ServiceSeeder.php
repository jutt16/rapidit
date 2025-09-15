<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Category;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Ac Service' => [
                'AC Gas Refill',
                'AC Servicing',
                'Washing Machine Installation',
            ],
            'Cook' => [
                'Cook Service',
            ],
            'Maid' => [
                'Maid Service',
            ],
            'Electrition' => [
                'Elect cat',
            ],
        ];

        foreach ($services as $categoryName => $serviceNames) {
            $category = Category::where('name', $categoryName)->first();
            if ($category) {
                foreach ($serviceNames as $name) {
                    Service::firstOrCreate([
                        'name' => $name,
                        'category_id' => $category->id,
                    ]);
                }
            }
        }
    }
}
