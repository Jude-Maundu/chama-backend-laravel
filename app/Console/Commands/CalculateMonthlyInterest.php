<?php

namespace App\Console\Commands;

use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Console\Command;

class CalculateMonthlyInterest extends Command
{
    protected $signature = 'chama:calculate-monthly-interest
                            {--rate=5 : Interest rate percentage per annum}
                            {--apply : Apply interest to member accounts}';
    
    protected $description = 'Calculate and apply monthly interest on member savings';

    public function handle()
    {
        $this->info('📈 Calculating monthly interest on savings...');
        
        $annualRate = (float) $this->option('rate');
        $monthlyRate = $annualRate / 12 / 100;
        
        $members = \App\Models\User::role('member')->where('is_active', true)->get();
        
        $interestTotal = 0;
        
        foreach ($members as $member) {
            // Get member's total savings
            $savings = $member->contributions()
                ->where('status', 'completed')
                ->sum('total_amount');
            
            $loansOutstanding = $member->loans()
                ->whereIn('status', ['approved', 'disbursed'])
                ->sum('balance');
            
            $netSavings = $savings - $loansOutstanding;
            $interest = $netSavings * $monthlyRate;
            
            if ($interest > 0) {
                $interestTotal += $interest;
                
                $this->line("   • {$member->name}: Savings " . number_format($netSavings, 2) . 
                           " → Interest " . number_format($interest, 2));
                
                if ($this->option('apply')) {
                    // Create interest transaction
                    Transaction::create([
                        'user_id' => $member->id,
                        'type' => 'interest',
                        'direction' => 'credit',
                        'amount' => $interest,
                        'balance_before' => $netSavings,
                        'balance_after' => $netSavings + $interest,
                        'description' => 'Monthly interest on savings',
                        'transaction_date' => now(),
                        'created_by' => 1, // System user
                    ]);
                }
            }
        }
        
        $this->newLine();
        $this->info("💰 Total interest calculated: " . number_format($interestTotal, 2));
        
        if ($this->option('apply')) {
            $this->info('✅ Interest applied to member accounts');
        }
        
        return Command::SUCCESS;
    }
}