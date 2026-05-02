<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'amount_invested',
        'current_value',
        'expected_return_rate',
        'actual_return',
        'investment_date',
        'maturity_date',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount_invested' => 'decimal:2',
        'current_value' => 'decimal:2',
        'expected_return_rate' => 'decimal:2',
        'actual_return' => 'decimal:2',
        'investment_date' => 'date',
        'maturity_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getReturnOnInvestmentAttribute()
    {
        if ($this->current_value && $this->amount_invested) {
            return (($this->current_value - $this->amount_invested) / $this->amount_invested) * 100;
        }
        return null;
    }
}