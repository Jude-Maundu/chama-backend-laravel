<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharityModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'name',
        'description',
        'target_amount',
        'current_amount',
        'currency_id',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
