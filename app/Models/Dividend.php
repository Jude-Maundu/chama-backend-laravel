<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dividend extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'total_amount',
        'total_shares',
        'per_share_amount',
        'calculation_date',
        'distribution_date',
        'status',
        'calculated_by',
        'approved_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_shares' => 'decimal:2',
        'per_share_amount' => 'decimal:2',
        'calculation_date' => 'date',
        'distribution_date' => 'date',
    ];

    public function calculator()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'dividend_user')
                    ->withPivot('amount', 'status')
                    ->withTimestamps();
    }
}