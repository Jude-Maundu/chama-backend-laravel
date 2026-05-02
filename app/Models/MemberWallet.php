<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'chama_id', 'wallet_type', 'external_wallet_id',
        'balance', 'is_primary', 'status'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function canTransfer($amount): bool
    {
        return $this->balance >= $amount && $this->status === 'active';
    }

    public function debit($amount)
    {
        $this->balance -= $amount;
        $this->save();
    }

    public function credit($amount)
    {
        $this->balance += $amount;
        $this->save();
    }
}
