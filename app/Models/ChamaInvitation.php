<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChamaInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id', 'invited_by', 'email', 'token', 'role', 'status', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function chama()
    {
        return $this->belongsTo(Chama::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}
