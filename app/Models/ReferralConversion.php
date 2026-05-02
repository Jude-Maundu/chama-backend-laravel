<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_id',
        'referred_user_id',
        'converted_at',
        'status',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    /**
     * Get the referral.
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    /**
     * Get the referred user.
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
