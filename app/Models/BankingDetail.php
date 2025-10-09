<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankingDetail extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc',
        'branch',
        'currency',
        'is_default',
        'status'
    ];

    // encrypt account number at rest
    protected $casts = [
        'account_number' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    // Safe masked representation for API responses
    public function masked_account()
    {
        $acc = $this->account_number ?? '';
        return strlen($acc) >= 4 ? '****' . substr($acc, -4) : '****';
    }
}
