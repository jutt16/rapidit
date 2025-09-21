<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'picture',
        'price',
        'tax',
        'commission_pct',
        'cancellation_charges',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax'   => 'decimal:2',
        'commission_pct' => 'float',
        'cancellation_charges' => 'decimal:2',
    ];

    /**
     * Category this service belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Partners offering this service (many-to-many).
     */
    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(
            PartnerProfile::class,
            'partner_service_preferences',
            'service_id',
            'partner_profile_id'
        )->withTimestamps();
    }

    /**
     * Convenience accessor: price + tax (assuming tax is an absolute amount).
     * If your tax is a percent, change logic accordingly.
     */
    public function getPriceWithTaxAttribute()
    {
        return (float) $this->price + (float) $this->tax;
    }
}
