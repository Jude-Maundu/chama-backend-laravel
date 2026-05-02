<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'symbol', 'decimal_places', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    public function chamas(): HasMany
    {
        return $this->hasMany(Chama::class, 'primary_currency_id');
    }

    public function financialGoals(): HasMany
    {
        return $this->hasMany(FinancialGoal::class);
    }

    public function expenseCategories(): BelongsToMany
    {
        return $this->belongsToMany(ExpenseCategory::class);
    }
}
