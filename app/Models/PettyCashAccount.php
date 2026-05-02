<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyCashAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id', 'name', 'description', 'balance', 'currency_id',
        'custodian_id', 'float_amount', 'status'
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(PettyCashClaim::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(PettyCashReconciliation::class);
    }

    public function getApprovedClaimsTotal()
    {
        return $this->claims()
                   ->where('status', 'paid')
                   ->sum('amount');
    }

    public function addFunds($amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function deductFunds($amount)
    {
        $this->balance = max(0, $this->balance - $amount);
        $this->save();
    }
}
