<?php

namespace App\Events;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanApproved implements ShouldBroadcast
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
            new PrivateChannel('treasurer'),
        ];
    }

    public function broadcastAs()
    {
        return 'loan.approved';
    }

    public function broadcastWith()
    {
        return [
            'loan_id' => $this->loan->id,
            'member_name' => $this->member->name,
            'amount' => $this->amount,
            'loan_type' => $this->loan->loan_type,
            'approval_date' => $this->loan->approval_date?->toDateTimeString(),
        ];
    }
}