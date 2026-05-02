<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberExitRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chama_id',
        'reason',
        'comments',
        'status',
        'refund_amount',
        'refund_status',
        'exit_date',
        'approved_by',
        'approval_notes',
        'approved_at',
        'processed_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'exit_date' => 'date',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user who is requesting to exit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chama.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    /**
     * Get the user who approved the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
