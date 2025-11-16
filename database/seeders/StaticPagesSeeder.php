<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaticPage;

class StaticPagesSeeder extends Seeder
{
    public function run()
    {
        // About page for users
        StaticPage::updateOrCreate(
            ['slug' => 'about-user'],
            [
                'title' => 'About Us',
                'content' => 'We provide top-notch services to our clients with professional partners, ensuring quality and trust.',
            ]
        );

        // About page for partners
        StaticPage::updateOrCreate(
            ['slug' => 'about-partner'],
            [
                'title' => 'About Us',
                'content' => 'Join our network of professional partners and grow your business with us.',
            ]
        );
    }
}
