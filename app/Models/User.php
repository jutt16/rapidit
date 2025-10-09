<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'phone_verified',
        'password',
        'role', // 'admin', 'partner', 'user'
        'status',
        'partner_status', // only used if role = partner
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // inside class User extends Authenticatable
    public function partnerProfile()
    {
        return $this->hasOne(\App\Models\PartnerProfile::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function availability()
    {
        return $this->hasOne(\App\Models\PartnerAvailability::class, 'partner_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function creditWallet($amount, $description = null)
    {
        $wallet = $this->wallet ?? Wallet::create(['user_id' => $this->id]);
        return $wallet->credit($amount, $description);
    }

    public function debitWallet($amount, $description = null)
    {
        $wallet = $this->wallet;
        if (!$wallet) {
            throw new \Exception("Wallet not found");
        }
        return $wallet->debit($amount, $description);
    }

    public function bankingDetails()
    {
        return $this->hasMany(\App\Models\BankingDetail::class);
    }
}
