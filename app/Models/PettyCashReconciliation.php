<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'petty_cash_account_id', 'reconciled_by', 'recorded_balance',
        'physical_count', 'variance', 'variance_explanation', 'status',
        'approved_by', 'approved_at', 'reconciliation_date'
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function pettyCashAccount(): BelongsTo
    {
        return $this->belongsTo(PettyCashAccount::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateVariance()
    {
        return $this->physical_count - $this->recorded_balance;
    }

    public function approve($notes = null)
    {
        $this->status = 'approved';
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->save();
    }
}
