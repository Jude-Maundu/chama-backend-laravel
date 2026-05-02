<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id',
        'name',
        'provider',
        'description',
        'premium_amount',
        'currency_id',
        'coverage_details',
        'status',
    ];

    protected $casts = [
        'premium_amount' => 'decimal:2',
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
