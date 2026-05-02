<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessOverdueLoans extends Command
{
    protected $signature = 'chama:process-overdue-loans
                            {--days=30 : Days overdue to mark as defaulted}
                            {--penalty-rate=5 : Penalty percentage on overdue amount}';
    
    protected $description = 'Process overdue loans and apply penalties';

    public function handle()
    {
        $this->info('💰 Processing overdue loans...');
        
        $defaultDays = (int) $this->option('days');
        $penaltyRate = (float) $this->option('penalty-rate');
        
        // Find overdue loans
        $overdueLoans = Loan::whereIn('status', ['approved', 'disbursed'])
            ->where('balance', '>', 0)
            ->where('next_payment_date', '<', Carbon::now())
            ->with('user')
            ->get();
        
        if ($overdueLoans->isEmpty()) {
            $this->info('✅ No overdue loans found.');
            return Command::SUCCESS;
        }
        
        $this->info("📋 Found {$overdueLoans->count()} overdue loan(s)");
        
        $penaltyTotal = 0;
        $defaultedCount = 0;
        
        foreach ($overdueLoans as $loan) {
            $daysOverdue = Carbon::parse($loan->next_payment_date)->diffInDays(now());
            $penalty = $loan->monthly_payment * ($penaltyRate / 100);
            
            $this->line("   • {$loan->user->name}: Loan #{$loan->id} - {$daysOverdue} days overdue");
            
            // Update loan with penalty
            $loan->increment('penalty_amount', $penalty);
            $penaltyTotal += $penalty;
            
            // Check if should be defaulted
            if ($daysOverdue >= $defaultDays) {
                $loan->update(['status' => 'defaulted']);
                $defaultedCount++;
                $this->line("      ⚠️ MARKED AS DEFAULTED");
            }
            
            // Send notification
            Notification::create([
                'user_id' => $loan->user_id,
                'title' => '⚠️ Loan Overdue',
                'message' => "Your loan payment is {$daysOverdue} days overdue. Penalty of " . number_format($penalty, 2) . 
                            " has been applied. Current balance: " . number_format($loan->balance, 2),
                'type' => 'loan',
                'channel' => 'in_app',
                'is_read' => false,
            ]);
        }
        
        $this->newLine();
        $this->info("💰 Total penalties applied: " . number_format($penaltyTotal, 2));
        $this->info("⚠️ Loans defaulted: {$defaultedCount}");
        $this->info('✅ Overdue loan processing completed!');
        
        return Command::SUCCESS;
    }
}