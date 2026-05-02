<?php

namespace App\Events;

use App\Models\Investment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvestmentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $investment;

    public function __construct(Investment $investment)
    {
        $this->investment = $investment;
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
        return 'investment.added';
    }

    public function broadcastWith()
    {
        return [
            'investment_id' => $this->investment->id,
            'name' => $this->investment->name,
            'type' => $this->investment->type,
            'amount' => $this->investment->amount_invested,
            'investment_date' => $this->investment->investment_date->toDateTimeString(),
        ];
    }
}