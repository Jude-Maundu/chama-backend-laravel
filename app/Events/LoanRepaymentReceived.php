<?php

namespace App\Events;

use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanRepaymentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $repayment;
    public $loan;
    public $member;

    public function __construct(LoanRepayment $repayment)
    {
        $this->repayment = $repayment;
        $this->loan = $repayment->loan;
        $this->member = $this->loan->user;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('member.' . $this->member->id),
            new PrivateChannel('treasurer'),
        ];
    }

    public function broadcastAs()
    {
        return 'loan.repayment';
    }

    public function broadcastWith()
    {
        return [
            'loan_id' => $this->loan->id,
            'installment_number' => $this->repayment->installment_number,
            'amount_paid' => $this->repayment->paid_amount,
            'remaining_balance' => $this->loan->balance,
        ];
    }
}