<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Contribution;
use App\Models\Notification;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendContributionReminders extends Command
{
    protected $signature = 'chama:send-contribution-reminders
                            {--days=3 : Days before due date to send reminder}
                            {--sms : Send SMS reminders}
                            {--email : Send email reminders}';
    
    protected $description = 'Send contribution reminders to members';

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    public function handle()
    {
        $this->info('📧 Sending contribution reminders...');
        
        $daysBefore = (int) $this->option('days');
        $dueDate = Carbon::now()->addDays($daysBefore);
        
        $members = User::role('member')->where('is_active', true)->get();
        $contributionAmount = Setting::get('monthly_contribution', 5000);
        
        $sentCount = 0;
        
        foreach ($members as $member) {
            // Check if already paid this month
            $alreadyPaid = Contribution::where('user_id', $member->id)
                ->whereMonth('payment_date', now()->month)
                ->where('status', 'completed')
                ->exists();
            
            if ($alreadyPaid) {
                continue;
            }
            
            // Create in-app notification
            Notification::create([
                'user_id' => $member->id,
                'title' => '💰 Contribution Reminder',
                'message' => "Your monthly contribution of " . number_format($contributionAmount, 2) . 
                            " is due on {$dueDate->format('M d, Y')}. Please make your payment on time.",
                'type' => 'reminder',
                'channel' => 'in_app',
                'is_read' => false,
            ]);
            
            // Send SMS if requested
            if ($this->option('sms') && $member->phone) {
                $this->smsService->send(
                    $member->phone,
                    "CHAMA REMINDER: Your contribution of " . number_format($contributionAmount, 2) . 
                    " is due on {$dueDate->format('M d, Y')}. Pay via M-Pesa Paybill 123456."
                );
            }
            
            $sentCount++;
            
            if ($sentCount % 10 === 0) {
                $this->line("   • Sent {$sentCount} reminders...");
            }
        }
        
        $this->newLine();
        $this->info("✅ Sent {$sentCount} reminder(s) to members");
        
        return Command::SUCCESS;
    }
}