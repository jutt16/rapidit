<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'estimated_time',
    ];

    /**
     * Services under this category.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
