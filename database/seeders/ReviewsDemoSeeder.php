<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::limit(3)->get();
        foreach ($bookings as $i => $booking) {
            if (!$booking->service) continue;
            Review::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'user_id' => $booking->user_id,
                    'partner_id' => optional($booking->service->partners()->first())->user_id,
                    'rating' => 4 + ($i % 2),
                    'comment' => 'Great service ' . ($i + 1),
                ]
            );
        }
    }
}


