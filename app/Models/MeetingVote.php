<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'topic',
        'vote_option',
        'notes',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}