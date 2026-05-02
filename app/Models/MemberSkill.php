<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chama_id',
        'skill_name',
        'proficiency_level',
        'description',
        'experience',
        'willing_to_share',
        'endorsements',
    ];

    protected $casts = [
        'willing_to_share' => 'boolean',
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
