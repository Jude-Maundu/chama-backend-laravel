<?php

namespace App\Listeners;

use App\Events\MeetingReminder;
use App\Models\Notification;
use App\Services\SmsService;

class SendMeetingReminderNotification
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(MeetingReminder $event)
    {
        Notification::create([
            'user_id' => $event->member->id,
            'title' => 'Meeting Reminder',
            'message' => "Reminder: {$event->meeting->title} on {$event->meeting->meeting_date}",
            'type' => 'meeting',
            'channel' => 'in_app',
            'is_read' => false,
        ]);
    }
}
