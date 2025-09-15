<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CookPricing extends Model
{
    protected $fillable = [
        'base_price',                   // Base price for 1 person, up to 2 dishes
        'additional_dish_charge',       // Charge per extra dish beyond 2
        'additional_person_percentage', // Percentage for each extra person
    ];

    /**
     * Calculate final cost based on persons and dishes
     *
     * @param int $persons
     * @param int $dishes
     * @return float
     */
    public static function calculateCost(int $persons, int $dishes): float
    {
        $pricing = self::first();

        $bp = $pricing->base_price;

        $ad = $dishes > 2 ? ($dishes - 2) * $pricing->additional_dish_charge : 0;

        $subtotal = $bp + $ad;

        if ($persons > 1) {
            $ap = ($pricing->additional_person_percentage / 100) * $subtotal;
            $fc = $subtotal + ($ap * ($persons - 1));
        } else {
            $fc = $subtotal;
        }

        return round($fc, 2);
    }
}
