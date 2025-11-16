<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $fillable = ['name', 'email', 'message', 'user_type', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
