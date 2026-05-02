<?php

namespace App\Events;

use App\Models\Meeting;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingScheduled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('members'),
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs()
    {
        return 'meeting.scheduled';
    }

    public function broadcastWith()
    {
        return [
            'meeting_id' => $this->meeting->id,
            'title' => $this->meeting->title,
            'meeting_date' => $this->meeting->meeting_date->toDateTimeString(),
            'venue' => $this->meeting->venue,
            'virtual_link' => $this->meeting->virtual_link,
        ];
    }
}