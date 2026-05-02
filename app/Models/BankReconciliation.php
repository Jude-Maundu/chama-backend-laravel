<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id', 'file_name', 'file_path', 'file_type', 'statement_date',
        'statement_balance', 'system_balance', 'variance', 'status',
        'total_transactions', 'matched_transactions', 'unmatched_transactions',
        'discrepancies', 'uploaded_by', 'reviewed_by', 'review_notes',
        'reviewed_at'
    ];

    protected $casts = [
        'statement_date' => 'date',
        'discrepancies' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getMatchPercentage(): float
    {
        if ($this->total_transactions == 0) {
            return 0;
        }
        return ($this->matched_transactions / $this->total_transactions) * 100;
    }

    public function approve()
    {
        $this->status = 'approved';
        $this->reviewed_by = auth()->id();
        $this->reviewed_at = now();
        $this->save();
    }
}
