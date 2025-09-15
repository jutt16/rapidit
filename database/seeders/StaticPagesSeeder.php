<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaticPage;

class StaticPagesSeeder extends Seeder
{
    public function run()
    {
        StaticPage::updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About Us',
                'content' => 'We provide top-notch services to our clients with professional partners, ensuring quality and trust.',
            ]
        );
    }
}
