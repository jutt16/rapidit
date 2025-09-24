<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_image',
        'email',
    ];

    /**
     * Relation: Profile belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
