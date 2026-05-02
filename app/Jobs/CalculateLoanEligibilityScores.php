<?php

namespace App\Jobs;

use App\Models\LoanEligibilityScore;
use App\Models\Chama;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateLoanEligibilityScores implements ShouldQueue
{
    use Queueable;

    public function __construct(private Chama $chama) {}

    public function handle(): void
    {
        // Recalculate eligibility scores for all active members
        $members = $this->chama->users()
                              ->wherePivot('status', 'active')
                              ->get();

        foreach ($members as $member) {
            $eligibilityController = app(\App\Http\Controllers\Api\LoanEligibilityController::class);
            
            try {
                $eligibilityController->calculateScore($this->chama, $member);
            } catch (\Exception $e) {
                \Log::error('Failed to calculate eligibility score', [
                    'member_id' => $member->id,
                    'chama_id' => $this->chama->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
