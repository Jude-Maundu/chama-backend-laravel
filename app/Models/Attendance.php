<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'status',
        'arrival_time',
        'excuse_reason',
        'signed_minutes',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'signed_minutes' => 'boolean',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }
}