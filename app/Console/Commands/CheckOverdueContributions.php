<?php

namespace App\Console\Commands;

use App\Models\Contribution;
use App\Models\User;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckOverdueContributions extends Command
{
    protected $signature = 'chama:check-overdue-contributions
                            {--days=5 : Days after due date to consider overdue}
                            {--apply-penalty : Automatically apply penalty}
                            {--notify : Send notifications to members}';
    
    protected $description = 'Check for overdue contributions and apply penalties';

    public function handle()
    {
        $this->info('🔍 Checking for overdue contributions...');
        
        $graceDays = (int) $this->option('days');
        $dueDate = Carbon::now()->subDays($graceDays);
        
        $overdueContributions = Contribution::where('status', 'pending')
            ->where('due_date', '<', $dueDate)
            ->with('user')
            ->get();
        
        if ($overdueContributions->isEmpty()) {
            $this->info('✅ No overdue contributions found.');
            return Command::SUCCESS;
        }
        
        $this->info("📋 Found {$overdueContributions->count()} overdue contribution(s)");
        
        $penaltyRate = Setting::get('late_penalty_percentage', 5);
        $totalPenalties = 0;
        
        foreach ($overdueContributions as $contribution) {
            $daysLate = Carbon::parse($contribution->due_date)->diffInDays(now());
            $penalty = $contribution->amount * ($penaltyRate / 100);
            
            $this->line("   • {$contribution->user->name}: Due {$contribution->due_date} ({$daysLate} days late)");
            
            if ($this->option('apply-penalty')) {
                $contribution->update([
                    'penalty' => $penalty,
                    'total_amount' => $contribution->amount + $penalty,
                ]);
                $totalPenalties += $penalty;
                $this->line("      💰 Penalty applied: " . number_format($penalty, 2));
            }
            
            if ($this->option('notify')) {
                Notification::create([
                    'user_id' => $contribution->user_id,
                    'title' => '⚠️ Overdue Contribution',
                    'message' => "Your contribution of " . number_format($contribution->amount, 2) . 
                                " was due on {$contribution->due_date}. Penalty of " . number_format($penalty, 2) . 
                                " has been applied. Please pay immediately.",
                    'type' => 'payment',
                    'channel' => 'in_app',
                    'is_read' => false,
                ]);
            }
        }
        
        $this->newLine();
        $this->info("💰 Total penalties applied: " . number_format($totalPenalties, 2));
        $this->info('✅ Overdue check completed!');
        
        return Command::SUCCESS;
    }
}