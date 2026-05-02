<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmergencyFund extends Model
{
    use HasFactory;

    protected $fillable = ['chama_id', 'total_balance', 'minimum_balance', 'purpose', 'status'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(EmergencyWithdrawal::class);
    }

    public function getAvailableBalance(): float
    {
        return max(0, $this->total_balance - $this->minimum_balance);
    }

    public function isHealthy(): bool
    {
        return $this->total_balance >= $this->minimum_balance;
    }

    public function addFunds($amount)
    {
        $this->total_balance += $amount;
        $this->save();
    }

    public function deductFunds($amount)
    {
        $this->total_balance = max(0, $this->total_balance - $amount);
        $this->save();
    }
}
