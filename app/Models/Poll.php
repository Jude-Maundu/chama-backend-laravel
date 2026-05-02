<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'created_by',
        'question',
        'description',
        'poll_type',
        'options',
        'is_anonymous',
        'allow_multiple_votes',
        'start_time',
        'end_time',
        'status',
        'results',
    ];

    protected $casts = [
        'options' => 'json',
        'results' => 'json',
        'is_anonymous' => 'boolean',
        'allow_multiple_votes' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the meeting.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the user who created the poll.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the votes for this poll.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
