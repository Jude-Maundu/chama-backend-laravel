<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'task',
        'description',
        'is_completed',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }
}
