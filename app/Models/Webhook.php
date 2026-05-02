<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'url',
        'event_type',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }
}
