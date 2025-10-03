<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'addressLine',
        'city',
        'state',
        'postalCode',
        'latitude',
        'longitude',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute()
    {
        return "{$this->addressLine}, {$this->city}, {$this->state}, {$this->postalCode}";
    }
}
