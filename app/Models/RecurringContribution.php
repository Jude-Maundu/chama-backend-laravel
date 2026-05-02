<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id', 'user_id', 'amount', 'frequency', 'payment_method',
        'status', 'start_date', 'next_due_date', 'last_processed_at',
        'failed_attempts', 'failure_reason', 'payment_reference'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'next_due_date' => 'datetime',
        'last_processed_at' => 'datetime',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPastDue(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }

    public function getDaysUntilDue(): int
    {
        return $this->next_due_date ? $this->next_due_date->diffInDays(now()) : 0;
    }
}
