<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Dividend;
use App\Models\Contribution;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateDividends implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $period;
    protected $totalProfit;

    public function __construct($period, $totalProfit)
    {
        $this->period = $period;
        $this->totalProfit = $totalProfit;
    }

    public function handle()
    {
        $totalShares = User::role('member')->sum('total_contributions');
        $perShareAmount = $totalShares > 0 ? $this->totalProfit / $totalShares : 0;
        
        $dividend = Dividend::create([
            'period' => $this->period,
            'total_amount' => $this->totalProfit,
            'total_shares' => $totalShares,
            'per_share_amount' => $perShareAmount,
            'calculation_date' => now(),
            'status' => 'calculated',
            'calculated_by' => 1,
        ]);
        
        $members = User::role('member')->get();
        foreach ($members as $member) {
            $shares = Contribution::where('user_id', $member->id)->sum('total_amount');
            $amount = $shares * $perShareAmount;
            
            $dividend->members()->attach($member->id, [
                'amount' => $amount,
                'status' => 'pending',
            ]);
        }
    }
}
