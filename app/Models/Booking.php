<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;   // <-- ADD THIS

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'address_id',
        'schedule_date',
        'schedule_time',
        'payment_method',
        'amount',
        'tax',
        'total_amount',
        'status', // newly added
        'service_time', // only for maid
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // User who made the booking
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Service (cook, maid, etc.)
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Address where service is scheduled
    public function address()
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    // Cook-specific details (only if service_id = 6)
    public function cookBooking()
    {
        return $this->hasOne(CookBooking::class);
    }

    // Maid package (matched by service_time in minutes)
    public function maidPackage()
    {
        return $this->belongsTo(MaidPricing::class, 'service_time', 'time');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    // Check if booking is for cook
    public function isCookBooking(): bool
    {
        return $this->service_id == 6;
    }

    // Check if booking is for maid
    public function isMaidBooking(): bool
    {
        return $this->service && strtolower($this->service->name) === 'maid';
    }

    // Calculate total amount (example logic, can be replaced)
    public function calculateTotal(): float
    {
        return round($this->amount + $this->tax, 2);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    // in Booking model
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class);
    }
}
