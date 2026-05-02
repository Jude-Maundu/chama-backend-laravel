<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\Attendance;
use App\Models\Notification;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendMeetingReminders extends Command
{
    protected $signature = 'chama:send-meeting-reminders
                            {--hours=24 : Hours before meeting to send reminder}
                            {--sms : Send SMS reminders}';
    
    protected $description = 'Send meeting reminders to all members';

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    public function handle()
    {
        $this->info('📅 Sending meeting reminders...');
        
        $hoursBefore = (int) $this->option('hours');
        $reminderTime = Carbon::now()->addHours($hoursBefore);
        
        $upcomingMeetings = Meeting::where('meeting_date', '>=', Carbon::now())
            ->where('meeting_date', '<=', $reminderTime)
            ->where('status', 'scheduled')
            ->get();
        
        if ($upcomingMeetings->isEmpty()) {
            $this->info('✅ No upcoming meetings found.');
            return Command::SUCCESS;
        }
        
        $sentCount = 0;
        
        foreach ($upcomingMeetings as $meeting) {
            $this->info("📌 Meeting: {$meeting->title} on {$meeting->meeting_date->format('M d, Y H:i')}");
            
            $members = \App\Models\User::role('member')->where('is_active', true)->get();
            
            foreach ($members as $member) {
                Notification::create([
                    'user_id' => $member->id,
                    'title' => '📢 Meeting Reminder',
                    'message' => "Reminder: {$meeting->title} on {$meeting->meeting_date->format('M d, Y \a\t H:i')} at {$meeting->venue}. Agenda: " . substr($meeting->agenda, 0, 100),
                    'type' => 'meeting',
                    'channel' => 'in_app',
                    'is_read' => false,
                    'data' => json_encode(['meeting_id' => $meeting->id]),
                ]);
                
                if ($this->option('sms') && $member->phone) {
                    $this->smsService->send(
                        $member->phone,
                        "CHAMA MEETING: {$meeting->title} on {$meeting->meeting_date->format('M d, Y H:i')} at {$meeting->venue}. Please attend."
                    );
                }
                
                $sentCount++;
            }
            
            $this->line("   • Sent reminders to {$members->count()} members");
        }
        
        $this->newLine();
        $this->info("✅ Sent {$sentCount} meeting reminder(s)");
        
        return Command::SUCCESS;
    }
}