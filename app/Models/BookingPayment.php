<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPayment extends Model
{
    protected $fillable = [
        'booking_id',
        'payment_method',
        'amount',
        'razorpay_link_id',
        'razorpay_short_url',
        'razorpay_link_status',
        'razorpay_payment_id',
        'razorpay_signature',
        'razorpay_payment_link_reference_id',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
