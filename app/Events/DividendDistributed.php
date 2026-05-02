<?php

namespace App\Events;

use App\Models\Dividend;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DividendDistributed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $dividend;
    public $member;
    public $amount;

    public function __construct(Dividend $dividend, User $member, $amount)
    {
        $this->dividend = $dividend;
        $this->member = $member;
        $this->amount = $amount;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('member.' . $this->member->id),
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs()
    {
        return 'dividend.distributed';
    }

    public function broadcastWith()
    {
        return [
            'dividend_id' => $this->dividend->id,
            'period' => $this->dividend->period,
            'amount' => $this->amount,
            'distribution_date' => $this->dividend->distribution_date?->toDateTimeString(),
        ];
    }
}