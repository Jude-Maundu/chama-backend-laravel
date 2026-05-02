<?php

namespace App\Events;

use App\Models\Contribution;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContributionReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contribution;
    public $member;
    public $amount;

    public function __construct(Contribution $contribution)
    {
        $this->contribution = $contribution;
        $this->member = $contribution->user;
        $this->amount = $contribution->total_amount;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('member.' . $this->member->id),
            new PrivateChannel('treasurer'),
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs()
    {
        return 'contribution.received';
    }

    public function broadcastWith()
    {
        return [
            'contribution_id' => $this->contribution->id,
            'member_name' => $this->member->name,
            'amount' => $this->amount,
            'payment_method' => $this->contribution->payment_method,
            'payment_date' => $this->contribution->payment_date->toDateTimeString(),
            'receipt_number' => $this->contribution->receipt_number,
        ];
    }
}