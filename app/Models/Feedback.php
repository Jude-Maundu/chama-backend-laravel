<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'chama_id',
        'user_id',
        'title',
        'content',
        'is_anonymous',
        'status',
        'upvotes',
        'downvotes',
        'response',
        'responded_by',
        'responded_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the chama.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    /**
     * Get the user who submitted the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who responded to the feedback.
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
