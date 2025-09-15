<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CookBooking extends Model
{
    protected $fillable = [
        'booking_id',
        'no_of_people',
        'food_type1',
        'food_type2',
        'no_of_dishes',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
