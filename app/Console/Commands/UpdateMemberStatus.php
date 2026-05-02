<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Contribution;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateMemberStatus extends Command
{
    protected $signature = 'chama:update-member-status
                            {--inactive-months=3 : Months of inactivity to deactivate}
                            {--reactivate : Reactivate members who have paid}';
    
    protected $description = 'Update member status based on activity';

    public function handle()
    {
        $this->info('👥 Updating member statuses...');
        
        $inactiveMonths = (int) $this->option('inactive-months');
        $cutoffDate = Carbon::now()->subMonths($inactiveMonths);
        
        // Find inactive members
        $inactiveMembers = User::role('member')
            ->where('is_active', true)
            ->whereDoesntHave('contributions', function($query) use ($cutoffDate) {
                $query->where('payment_date', '>=', $cutoffDate)
                      ->where('status', 'completed');
            })
            ->get();
        
        if ($inactiveMembers->isNotEmpty()) {
            $this->info("📋 Found {$inactiveMembers->count()} inactive member(s)");
            
            foreach ($inactiveMembers as $member) {
                $lastContribution = $member->contributions()
                    ->where('status', 'completed')
                    ->latest()
                    ->first();
                
                $this->line("   • {$member->name}: Last payment " . 
                    ($lastContribution ? $lastContribution->payment_date->diffForHumans() : 'never'));
                
                $member->update(['is_active' => false]);
            }
            
            $this->info("✅ Deactivated {$inactiveMembers->count()} inactive member(s)");
        }
        
        // Reactivate members who have paid recently
        if ($this->option('reactivate')) {
            $reactivationDate = Carbon::now()->subDays(30);
            
            $reactivateMembers = User::role('member')
                ->where('is_active', false)
                ->whereHas('contributions', function($query) use ($reactivationDate) {
                    $query->where('payment_date', '>=', $reactivationDate)
                          ->where('status', 'completed');
                })
                ->get();
            
            if ($reactivateMembers->isNotEmpty()) {
                foreach ($reactivateMembers as $member) {
                    $member->update(['is_active' => true]);
                    $this->line("   • Reactivated: {$member->name}");
                }
                
                $this->info("✅ Reactivated {$reactivateMembers->count()} member(s)");
            }
        }
        
        return Command::SUCCESS;
    }
}