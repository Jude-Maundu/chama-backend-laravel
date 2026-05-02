<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'name',
        'description',
        'template_type',
        'agenda_items',
        'required_attendees',
        'expected_duration',
        'location_template',
        'requires_voting',
        'is_public',
    ];

    protected $casts = [
        'agenda_items' => 'json',
        'required_attendees' => 'json',
        'requires_voting' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Get the chama.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }
}
