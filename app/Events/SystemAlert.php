<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $level;
    public $message;
    public $context;

    public function __construct($level, $message, $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('system'),
        ];
    }

    public function broadcastAs()
    {
        return 'system.alert';
    }

    public function broadcastWith()
    {
        return [
            'level' => $this->level, // info, warning, error, critical
            'message' => $this->message,
            'context' => $this->context,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}