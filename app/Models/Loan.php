<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chama_id',
        'loan_type',
        'amount',
        'interest_rate',
        'duration_months',
        'monthly_payment',
        'total_payable',
        'balance',
        'penalty_amount',
        'purpose',
        'status',
        'application_date',
        'approval_date',
        'disbursement_date',
        'first_payment_date',
        'next_payment_date',
        'completion_date',
        'approved_by',
        'mpesa_transaction_id',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'balance' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'application_date' => 'date',
        'approval_date' => 'date',
        'disbursement_date' => 'date',
        'first_payment_date' => 'date',
        'next_payment_date' => 'date',
        'completion_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function guarantors()
    {
        return $this->belongsToMany(User::class, 'guarantors', 'loan_id', 'guarantor_id')
                    ->withPivot('amount_guaranteed', 'status');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'reference');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'disbursed']);
    }

    public function getIsOverdueAttribute()
    {
        return $this->next_payment_date && $this->next_payment_date < now() && $this->balance > 0;
    }
}