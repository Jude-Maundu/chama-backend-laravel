<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'title',
        'type',
        'description',
        'agenda',
        'meeting_date',
        'venue',
        'virtual_link',
        'duration_minutes',
        'minutes',
        'minutes_file',
        'status',
        'send_notifications',
        'require_rsvp',
        'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
    ];

    public function chama()
    {
        return $this->belongsTo(Chama::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'attendance')
                    ->withPivot('status', 'arrival_time', 'signed_minutes')
                    ->withTimestamps();
    }

    public function votes()
    {
        return $this->hasMany(MeetingVote::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('meeting_date', '>', now())->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}