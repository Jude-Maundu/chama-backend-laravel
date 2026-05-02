<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id', 'name', 'description', 'target_amount', 'current_amount',
        'currency_id', 'target_date', 'status', 'icon', 'priority'
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getProgressPercentage(): float
    {
        if ($this->target_amount == 0) {
            return 0;
        }
        return min(100, ($this->current_amount / $this->target_amount) * 100);
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    public function isOnTrack(): bool
    {
        $daysRemaining = now()->diffInDays($this->target_date, false);
        if ($daysRemaining <= 0) {
            return $this->status === 'completed';
        }

        $requiredDailyAmount = $this->getRemainingAmount() / $daysRemaining;
        $dailyContribution = $this->current_amount / now()->diffInDays($this->created_at, false);

        return $dailyContribution >= $requiredDailyAmount;
    }
}
