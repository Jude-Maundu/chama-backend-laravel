<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $member;
    public $reason;

    public function __construct(User $member, $reason = null)
    {
        $this->member = $member;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('treasurer'),
        ];
    }

    public function broadcastAs()
    {
        return 'member.left';
    }

    public function broadcastWith()
    {
        return [
            'member_id' => $this->member->id,
            'member_name' => $this->member->name,
            'reason' => $this->reason,
            'left_date' => now()->toDateTimeString(),
        ];
    }
}