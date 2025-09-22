<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // Credit wallet
    public function credit($amount, $description = null)
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    // Debit wallet
    public function debit($amount, $description = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception("Insufficient balance");
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'description' => $description,
        ]);
    }
}
