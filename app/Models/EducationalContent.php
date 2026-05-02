<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'content_type',
        'url',
        'is_premium',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
    ];
}
