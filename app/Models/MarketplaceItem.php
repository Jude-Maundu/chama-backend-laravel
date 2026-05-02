<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'user_id',
        'name',
        'description',
        'price',
        'currency_id',
        'type',
        'status',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
