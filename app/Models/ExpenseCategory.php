<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id', 'name', 'description', 'color', 'icon',
        'budget_limit', 'requires_approval', 'sort_order'
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ChamaExpense::class);
    }

    public function getTotalExpenses()
    {
        return $this->expenses()
                   ->where('status', 'approved')
                   ->sum('amount');
    }

    public function getRemainingBudget()
    {
        if (!$this->budget_limit) {
            return null;
        }
        return $this->budget_limit - $this->getTotalExpenses();
    }

    public function isOverBudget(): bool
    {
        if (!$this->budget_limit) {
            return false;
        }
        return $this->getTotalExpenses() > $this->budget_limit;
    }
}
