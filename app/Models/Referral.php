<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'chama_id',
        'referral_code',
        'referral_link',
        'points_earned',
        'bonus_amount',
        'successful_referrals',
        'referred_emails',
        'status',
    ];

    protected $casts = [
        'referred_emails' => 'array',
        'bonus_amount' => 'decimal:2',
    ];

    /**
     * Get the referrer.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the chama.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    /**
     * Get the conversions for this referral.
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(ReferralConversion::class);
    }
}
