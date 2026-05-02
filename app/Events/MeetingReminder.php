<?php

namespace App\Events;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingReminder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meeting;
    public $member;

    public function __construct(Meeting $meeting, User $member)
    {
        $this->meeting = $meeting;
        $this->member = $member;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('member.' . $this->member->id),
        ];
    }

    public function broadcastAs()
    {
        return 'meeting.reminder';
    }

    public function broadcastWith()
    {
        return [
            'meeting_id' => $this->meeting->id,
            'title' => $this->meeting->title,
            'meeting_date' => $this->meeting->meeting_date->toDateTimeString(),
            'venue' => $this->meeting->venue,
            'hours_until' => now()->diffInHours($this->meeting->meeting_date),
        ];
    }
}