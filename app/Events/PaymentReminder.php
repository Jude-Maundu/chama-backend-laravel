<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReminder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $member;
    public $amount;
    public $dueDate;

    public function __construct(User $member, $amount, $dueDate)
    {
        $this->member = $member;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('member.' . $this->member->id),
        ];
    }

    public function broadcastAs()
    {
        return 'payment.reminder';
    }

    public function broadcastWith()
    {
        return [
            'member_name' => $this->member->name,
            'amount' => $this->amount,
            'due_date' => $this->dueDate,
            'days_until_due' => now()->diffInDays($this->dueDate),
        ];
    }
}