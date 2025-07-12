<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory,HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'wallet',
        'profit_wallet',
        'refer_by',
        'refer_code',
        'is_active',
        'is_block',
        'password',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refer_by');
    }


    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'refer_by');
    }

    public function totalTeamMembersCount(int $level = 1): int
    {
        $count = $this->referrals()->count();

        foreach ($this->referrals as $referral) {
            $count += $referral->totalTeamMembersCount($level + 1);
        }

        return $count;
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->refer_code = self::generateReferCode();
        });
    }

    public static function generateReferCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        } while (self::where('refer_code', $code)->exists());

        return $code;
    }



    public function investor()
    {
        return $this->hasOne(Investor::class, 'user_id');
    }

    public function totalTeamInvestment(int $maxLevel = 3, int $currentLevel = 1): float
    {
        if ($currentLevel > $maxLevel) {
            return 0;
        }

        $total = 0;

        // get direct referrals
        $referrals = $this->referrals()->with('investor')->get();

        foreach ($referrals as $referral) {
            $investment = $referral->investor ? $referral->investor->investment : 0;
            $total += $investment;
            $total += $referral->totalTeamInvestment($maxLevel, $currentLevel + 1);
        }

        return $total;
    }

}


