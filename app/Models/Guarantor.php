<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    use HasFactory;

    protected $table = 'guarantors';

    protected $fillable = [
        'loan_id',
        'guarantor_id',
        'member_id',
        'amount_guaranteed',
        'status',
    ];

    protected $casts = [
        'amount_guaranteed' => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function guarantor()
    {
        return $this->belongsTo(User::class, 'guarantor_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}