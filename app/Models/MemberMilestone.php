<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chama_id',
        'milestone_type',
        'title',
        'description',
        'milestone_date',
        'is_notified',
        'badge_type',
        'points_earned',
    ];

    protected $casts = [
        'milestone_date' => 'date',
        'is_notified' => 'boolean',
    ];

    /**
     * Get the user.
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
}
