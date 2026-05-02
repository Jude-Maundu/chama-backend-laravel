<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_amount',
        'paid_amount',
        'principal_paid',
        'interest_paid',
        'penalty_paid',
        'balance_after',
        'due_date',
        'paid_date',
        'status',
        'transaction_id',
        'payment_method',
    ];

    protected $casts = [
        'due_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'penalty_paid' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'reference');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->where('status', 'pending');
    }
}