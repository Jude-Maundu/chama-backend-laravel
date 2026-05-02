<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'ticket_cost',
        'guests_count',
        'registered_at',
        'attended_at',
    ];

    protected $casts = [
        'ticket_cost' => 'decimal:2',
        'registered_at' => 'datetime',
        'attended_at' => 'datetime',
    ];

    /**
     * Get the event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
