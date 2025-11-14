<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'banking_detail_id',
        'amount',
        'fee',
        'currency',
        'status',
        'reference',
        'admin_note',
        'processed_by',
        'processed_at',
        'gateway',
        'gateway_payout_id',
        'gateway_status',
        'utr',
        'failure_reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function bankingDetail()
    {
        return $this->belongsTo(BankingDetail::class);
    }
}
