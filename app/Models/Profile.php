<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'national_id',
        'gender',
        'dob',
        'occupation',
        'address',
        'city',
        'postal_code',
        'emergency_contact_name',
        'emergency_contact_phone',
        'bank_name',
        'bank_account_number',
        'mpesa_number',
        'join_date',
        'exit_date',
        'notes',
        'profile_photo',
        'id_document',
    ];

    protected $casts = [
        'dob' => 'date',
        'join_date' => 'date',
        'exit_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAgeAttribute()
    {
        return $this->dob ? $this->dob->age : null;
    }
}