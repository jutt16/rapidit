<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaidPricing extends Model
{
    protected $fillable = [
        'time',  // in minutes
        'price', // package price
        'discount', // discount percentage
    ];
}
