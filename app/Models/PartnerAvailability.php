<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerAvailability extends Model
{
    protected $table = 'partner_availabilities';

    protected $fillable = [
        'partner_id',
        'is_available',
        'status',
        'start_time',
        'end_time',
        'blocked_dates',
    ];

    protected $casts = [
        'is_available'  => 'boolean',
        'blocked_dates' => 'array', // JSON → PHP array
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    /**
     * Check if partner is available for given date & time
     */
    public function isAvailableFor(string $date, string $timeRange): bool
    {
        if (!($this->is_available && $this->status === 'available')) {
            return false;
        }

        // Blocked date?
        if (!empty($this->blocked_dates) && in_array($date, $this->blocked_dates)) {
            return false;
        }

        // Time check
        [$start, $end] = explode('-', $timeRange);

        if ($this->start_time && $this->end_time) {
            return $start >= $this->start_time && $end <= $this->end_time;
        }

        return true; // No time restriction → available
    }
}
