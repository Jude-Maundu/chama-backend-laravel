<?php

namespace App\Jobs;

use App\Models\FinancialGoal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateFinancialGoalProgress implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Update financial goal progress based on recent contributions
        $goals = FinancialGoal::where('status', 'active')
                             ->get();

        foreach ($goals as $goal) {
            $this->calculateProgress($goal);
        }
    }

    private function calculateProgress(FinancialGoal $goal): void
    {
        // Get contributions to this Chama since last update
        $contributions = $goal->chama
            ->contributions()
            ->where('status', 'completed')
            ->where('created_at', '>=', $goal->updated_at)
            ->sum('amount');

        if ($contributions > 0) {
            $goal->current_amount += $contributions;

            if ($goal->current_amount >= $goal->target_amount) {
                $goal->status = 'completed';
            }

            $goal->save();
        }
    }
}
