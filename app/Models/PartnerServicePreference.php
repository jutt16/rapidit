<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerServicePreference extends Pivot
{
    protected $table = 'partner_service_preferences';

    protected $fillable = [
        'partner_profile_id',
        'service_id',
        'own_tools_available',
    ];

    protected $casts = [
        'own_tools_available' => 'boolean', // auto-casts to true/false
    ];

    public $timestamps = true;

    public function partnerProfile(): BelongsTo
    {
        return $this->belongsTo(PartnerProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
