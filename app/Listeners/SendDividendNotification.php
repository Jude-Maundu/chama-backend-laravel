<?php

namespace App\Listeners;

use App\Events\DividendDistributed;
use App\Models\Notification;
use App\Services\SmsService;

class SendDividendNotification
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(DividendDistributed $event)
    {
        Notification::create([
            'user_id' => $event->member->id,
            'title' => 'Dividend Distributed',
            'message' => "Dividend of " . number_format($event->amount, 2) . " for period {$event->dividend->period} has been sent.",
            'type' => 'dividend',
            'channel' => 'in_app',
            'is_read' => false,
        ]);
    }
}
