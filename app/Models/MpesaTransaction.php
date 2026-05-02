<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_request_id',
        'checkout_request_id',
        'mpesa_receipt_number',
        'amount',
        'phone_number',
        'transaction_type',
        'status',
        'result_code',
        'result_description',
        'user_id',
        'reference_id',
        'reference_type',
        'callback_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'callback_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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