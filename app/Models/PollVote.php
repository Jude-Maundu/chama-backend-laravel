<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'user_id',
        'vote_choice',
        'vote_weight',
        'voted_at',
    ];

    protected $casts = [
        'vote_weight' => 'decimal:2',
        'voted_at' => 'datetime',
    ];

    /**
     * Get the poll.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Get the user who voted.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
