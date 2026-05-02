<?php

namespace App\Listeners;

use App\Events\ContributionReceived;
use App\Models\Notification;
use App\Services\SmsService;

class SendContributionNotification
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(ContributionReceived $event)
    {
        Notification::create([
            'user_id' => $event->member->id,
            'title' => 'Contribution Received',
            'message' => "Your contribution of " . number_format($event->amount, 2) . " has been received.",
            'type' => 'payment',
            'channel' => 'in_app',
            'is_read' => false,
        ]);

        if ($event->member->phone) {
            $this->smsService->send(
                $event->member->phone,
                "CHAMA: Your contribution of " . number_format($event->amount, 2) . " has been received."
            );
        }
    }
}
