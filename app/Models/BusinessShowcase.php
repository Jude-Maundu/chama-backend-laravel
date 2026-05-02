<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessShowcase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chama_id',
        'business_name',
        'description',
        'industry',
        'logo',
        'website',
        'contact_info',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }
}
