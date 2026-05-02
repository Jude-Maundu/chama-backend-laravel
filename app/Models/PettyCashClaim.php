<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'petty_cash_account_id', 'user_id', 'description', 'amount',
        'status', 'approved_by', 'approval_notes', 'receipt_path',
        'receipt_files', 'approved_at', 'paid_at'
    ];

    protected $casts = [
        'receipt_files' => 'array',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function pettyCashAccount(): BelongsTo
    {
        return $this->belongsTo(PettyCashAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve($notes = null)
    {
        $this->status = 'approved';
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->save();
    }

    public function pay()
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Claim must be approved first');
        }

        $this->pettyCashAccount->deductFunds($this->amount);
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();
    }
}
