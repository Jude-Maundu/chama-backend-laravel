<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChamaMember extends Model
{
    use HasFactory;

    protected $table = 'chama_members';

    protected $fillable = [
        'chama_id', 'user_id', 'role', 'status', 'position',
        'joined_at', 'exited_at', 'permissions', 'notes', 'invited_by'
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'date',
        'exited_at' => 'date',
    ];

    public function chama()
    {
        return $this->belongsTo(Chama::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
