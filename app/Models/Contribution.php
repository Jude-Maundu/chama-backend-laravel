<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'penalty',
        'total_amount',
        'payment_method',
        'transaction_id',
        'mpesa_receipt',
        'payment_date',
        'due_date',
        'status',
        'receipt_number',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'reference');
    }

    public function mpesaTransaction()
    {
        return $this->belongsTo(MpesaTransaction::class, 'transaction_id', 'checkout_request_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}