<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_wallet_id', 'to_wallet_id', 'chama_id', 'amount',
        'status', 'transaction_reference', 'notes', 'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(MemberWallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(MemberWallet::class, 'to_wallet_id');
    }

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function processTransfer()
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Transfer is not in pending status');
        }

        if (!$this->fromWallet->canTransfer($this->amount)) {
            $this->status = 'failed';
            $this->save();
            throw new \Exception('Insufficient balance');
        }

        $this->fromWallet->debit($this->amount);
        $this->toWallet->credit($this->amount);

        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();

        return true;
    }
}
