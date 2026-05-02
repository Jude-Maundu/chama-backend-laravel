<?php

namespace App\Listeners;

use App\Events\ContributionReceived;
use App\Models\Transaction;

class UpdateMemberStats
{
    public function handle(ContributionReceived $event)
    {
        $user = $event->member;
        
        $currentBalance = Transaction::where('user_id', $user->id)->sum('amount');
        
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'contribution',
            'direction' => 'credit',
            'amount' => $event->amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance + $event->amount,
            'description' => 'Monthly contribution',
            'transaction_date' => now(),
            'created_by' => $user->id,
        ]);
    }
}
