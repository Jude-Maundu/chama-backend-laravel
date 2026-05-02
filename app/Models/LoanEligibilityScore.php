<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanEligibilityScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'chama_id', 'score', 'rating', 'recommended_loan_limit',
        'recommended_interest_rate', 'factors', 'risk_level', 'contribution_score',
        'repayment_score', 'attendance_score', 'communication_score',
        'default_history_score', 'calculated_at', 'expires_at'
    ];

    protected $casts = [
        'factors' => 'array',
        'calculated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getRiskColor(): string
    {
        return match($this->risk_level) {
            'low' => '#10b981',
            'medium' => '#f59e0b',
            'high' => '#ef4444',
            'very_high' => '#991b1b',
            default => '#6b7280'
        };
    }
}
