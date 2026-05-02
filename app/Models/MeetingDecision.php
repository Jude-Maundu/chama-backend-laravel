<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'decision_title',
        'decision_description',
        'assigned_to',
        'due_date',
        'priority',
        'status',
        'owner_user_id',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the meeting.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the user assigned to this decision.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
