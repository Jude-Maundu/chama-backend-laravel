<?php

namespace App\Services;

use App\Models\FinancialGoal;
use App\Models\Chama;
use Illuminate\Database\Eloquent\Collection;

class FinancialGoalService
{
    /**
     * Get goals on track
     */
    public function getGoalsOnTrack(Chama $chama): Collection
    {
        return $chama->financialGoals()
                    ->where('status', 'active')
                    ->get()
                    ->filter(fn($goal) => $goal->isOnTrack());
    }

    /**
     * Get goals behind schedule
     */
    public function getGoalsBehindSchedule(Chama $chama): Collection
    {
        return $chama->financialGoals()
                    ->where('status', 'active')
                    ->get()
                    ->filter(fn($goal) => !$goal->isOnTrack());
    }

    /**
     * Get upcoming target dates
     */
    public function getUpcomingDeadlines(Chama $chama, int $daysAhead = 30): Collection
    {
        $startDate = now();
        $endDate = now()->addDays($daysAhead);

        return $chama->financialGoals()
                    ->where('status', 'active')
                    ->whereBetween('target_date', [$startDate, $endDate])
                    ->orderBy('target_date')
                    ->get();
    }

    /**
     * Calculate total target amount
     */
    public function getTotalTargetAmount(Chama $chama): float
    {
        return $chama->financialGoals()
                    ->where('status', 'active')
                    ->sum('target_amount');
    }

    /**
     * Calculate total current amount across all goals
     */
    public function getTotalCurrentAmount(Chama $chama): float
    {
        return $chama->financialGoals()
                    ->where('status', 'active')
                    ->sum('current_amount');
    }

    /**
     * Get overall progress percentage
     */
    public function getOverallProgressPercentage(Chama $chama): float
    {
        $total = $this->getTotalTargetAmount($chama);
        if ($total == 0) {
            return 0;
        }

        return ($this->getTotalCurrentAmount($chama) / $total) * 100;
    }

    /**
     * Mark goal as completed
     */
    public function markCompleted(FinancialGoal $goal): FinancialGoal
    {
        $goal->update(['status' => 'completed']);
        return $goal;
    }
}
