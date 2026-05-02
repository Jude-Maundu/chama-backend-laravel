<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'created_by',
        'name',
        'description',
        'event_type',
        'start_date',
        'end_date',
        'location',
        'venue_url',
        'ticket_price',
        'max_attendees',
        'status',
        'images',
        'cover_image',
        'agenda',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'ticket_price' => 'decimal:2',
        'images' => 'json',
    ];

    /**
     * Get the chama.
     */
    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    /**
     * Get the user who created the event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attendees for this event.
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }
}
