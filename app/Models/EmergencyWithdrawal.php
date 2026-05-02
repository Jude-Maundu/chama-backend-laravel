<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_fund_id', 'user_id', 'chama_id', 'amount', 'reason',
        'description', 'status', 'approved_by', 'approval_notes',
        'approved_at', 'processed_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function emergencyFund(): BelongsTo
    {
        return $this->belongsTo(EmergencyFund::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
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

    public function reject($notes = null)
    {
        $this->status = 'rejected';
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->save();
    }

    public function process()
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Withdrawal must be approved first');
        }

        $this->emergencyFund->deductFunds($this->amount);
        $this->status = 'processed';
        $this->processed_at = now();
        $this->save();
    }
}
