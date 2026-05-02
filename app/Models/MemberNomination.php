<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberNomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'nominating_member_id',
        'nominated_member_id',
        'nomination_type',
        'status',
        'share_percentage',
        'notes',
        'accepted_at',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the chama.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    /**
     * Get the member who is nominating.
     */
    public function nominatingMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominating_member_id');
    }

    /**
     * Get the member who is nominated.
     */
    public function nominatedMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_member_id');
    }
}
