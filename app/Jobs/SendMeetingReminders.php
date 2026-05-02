<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\User;
use App\Services\SmsService;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMeetingReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function handle(SmsService $smsService)
    {
        $members = User::role('member')->where('is_active', true)->get();
        
        foreach ($members as $member) {
            Notification::create([
                'user_id' => $member->id,
                'title' => 'Meeting Reminder',
                'message' => "Reminder: {$this->meeting->title} on {$this->meeting->meeting_date} at {$this->meeting->venue}",
                'type' => 'meeting',
                'channel' => 'in_app',
                'is_read' => false,
            ]);
            
            if ($member->phone) {
                $smsService->send($member->phone, "CHAMA MEETING: {$this->meeting->title} on {$this->meeting->meeting_date->format('M d, Y H:i')} at {$this->meeting->venue}");
            }
        }
    }
}
