<?php

namespace App\Events;

use App\Models\Loan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanDisbursed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $loan;
    public $member;
    public $amount;

    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
        $this->member = $loan->user;
        $this->amount = $loan->amount;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('member.' . $this->member->id),
        ];
    }

    public function broadcastAs()
    {
        return 'loan.disbursed';
    }

    public function broadcastWith()
    {
        return [
            'loan_id' => $this->loan->id,
            'amount' => $this->amount,
            'mpesa_transaction_id' => $this->loan->mpesa_transaction_id,
            'disbursement_date' => $this->loan->disbursement_date?->toDateTimeString(),
        ];
    }
}