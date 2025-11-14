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
        'failure_reason',
        'wallet_transaction_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function bankingDetail()
    {
        return $this->belongsTo(BankingDetail::class);
    }

    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function syncWalletTransactionNote(?string $statusOverride = null): void
    {
        if (!$this->wallet_transaction_id) {
            return;
        }

        $status = $statusOverride ?? $this->status ?? 'processing';

        $label = match ($status) {
            'completed' => 'completed',
            'failed' => 'failed',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'pending', 'approved', 'processing' => 'processing',
            default => $status,
        };

        $note = sprintf('Withdrawal #%d (%s)', $this->id, $label);

        $this->walletTransaction()->whereKey($this->wallet_transaction_id)->update([
            'description' => $note,
        ]);
    }
}
