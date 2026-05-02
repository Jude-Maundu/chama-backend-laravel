<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $member;

    public function __construct(User $member)
    {
        $this->member = $member;
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
        return 'member.joined';
    }

    public function broadcastWith()
    {
        return [
            'member_id' => $this->member->id,
            'member_name' => $this->member->name,
            'member_phone' => $this->member->phone,
            'member_email' => $this->member->email,
            'joined_date' => $this->member->created_at->toDateTimeString(),
        ];
    }
}