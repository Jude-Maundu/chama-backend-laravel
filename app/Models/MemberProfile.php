<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chama_id',
        'loyalty_points',
        'bio',
        'profession',
        'company',
        'website',
        'location',
        'skills',
        'interests',
        'linkedin',
        'twitter',
        'instagram',
        'show_contact_info',
        'show_profile_publicly',
    ];

    protected $casts = [
        'skills' => 'array',
        'interests' => 'array',
        'show_contact_info' => 'boolean',
        'show_profile_publicly' => 'boolean',
        'loyalty_points' => 'integer',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chama that the profile belongs to.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }
}
