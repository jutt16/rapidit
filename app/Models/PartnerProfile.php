<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PartnerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'gender',
        'date_of_birth',
        'profile_picture',
        'languages',
        'years_of_experience',
        'average_rating',
        'reviews_count',
        'latitude',
        'longitude',
        'aadhar_card',
        'pan_card',
        'police_verification',
        'covid_vaccination_certificate',
        'selfie_with_costume',
        'intro_video',
    ];

    protected $casts = [
        'languages' => 'array',
        'date_of_birth' => 'date',
        'years_of_experience' => 'integer',
    ];

    /**
     * The user that owns this partner profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Services this partner offers (many-to-many).
     * Uses partner_service_preferences pivot table.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'partner_service_preferences',
            'partner_profile_id',
            'service_id'
        )
            ->withPivot('own_tools_available')
            ->withTimestamps()
            ->using(\App\Models\PartnerServicePreference::class); // ✅ use custom pivot
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'partner_id');
    }
}
