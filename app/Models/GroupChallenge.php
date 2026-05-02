<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'created_by',
        'name',
        'description',
        'challenge_type',
        'start_date',
        'end_date',
        'target_amount',
        'reward_amount',
        'status',
        'participants',
        'winners',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'participants' => 'json',
        'winners' => 'json',
    ];

    /**
     * Get the chama.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    /**
     * Get the user who created the challenge.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
