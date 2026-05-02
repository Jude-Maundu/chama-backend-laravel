<?php

namespace App\Http\Controllers\Api;

use App\Models\LoanEligibilityScore;
use App\Models\Chama;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LoanEligibilityController extends Controller
{
    /**
     * Get eligibility score for a member
     */
    public function show(Chama $chama, User $user)
    {
        $this->authorize('view-member', $chama);

        $score = $user->loanEligibilityScores()
                     ->where('chama_id', $chama->id)
                     ->first();

        if (!$score || $score->isExpired()) {
            $score = $this->calculateScore($chama, $user);
        }

        return response()->json([
            'score' => $score,
            'details' => [
                'can_borrow' => $score->score >= 50,
                'loan_limit' => $score->recommended_loan_limit,
                'interest_rate' => $score->recommended_interest_rate,
                'risk_assessment' => $score->risk_level,
            ]
        ]);
    }

    /**
     * Calculate eligibility score
     */
    public function calculateScore(Chama $chama, User $user): LoanEligibilityScore
    {
        $member = $user->chamaMemberships()
                      ->where('chama_id', $chama->id)
                      ->first();

        if (!$member) {
            throw new \Exception('User is not a member of this Chama');
        }

        // Calculate contribution score (0-30)
        $totalContributions = $user->contributions()
                                  ->where('chama_id', $chama->id)
                                  ->sum('amount');
        $contribution_score = min(30, ($totalContributions / 10000) * 30);

        // Calculate repayment score (0-25)
        $loans = $user->loans()->where('chama_id', $chama->id)->get();
        $defaulted = $loans->where('status', 'defaulted')->count();
        $completed = $loans->where('status', 'completed')->count();
        $repayment_score = $completed > 0 ? ($completed - ($defaulted * 2)) * 5 : 0;
        $repayment_score = min(25, max(0, $repayment_score));

        // Calculate attendance score (0-20)
        $attendance = $user->attendances()
                          ->where('chama_id', $chama->id)
                          ->where('status', 'present')
                          ->count();
        $attendance_score = min(20, ($attendance / 10) * 20);

        // Calculate communication score (0-15)
        $communication_score = 15; // Default positive

        // Calculate default history score (0-10)
        $default_history_score = 10 - ($defaulted * 2);
        $default_history_score = max(0, $default_history_score);

        $total_score = $contribution_score + $repayment_score + $attendance_score 
                      + $communication_score + $default_history_score;

        // Determine rating and risk level
        $rating = match(true) {
            $total_score >= 80 => 'excellent',
            $total_score >= 60 => 'good',
            $total_score >= 40 => 'fair',
            default => 'poor'
        };

        $risk_level = match($rating) {
            'excellent' => 'low',
            'good' => 'medium',
            'fair' => 'high',
            default => 'very_high'
        };

        // Calculate recommended loan limit and interest rate
        $recommended_loan_limit = match($rating) {
            'excellent' => $totalContributions * 3,
            'good' => $totalContributions * 2,
            'fair' => $totalContributions * 1,
            default => $totalContributions * 0.5
        };

        $recommended_interest_rate = match($rating) {
            'excellent' => 8.00,
            'good' => 12.00,
            'fair' => 18.00,
            default => 25.00
        };

        $score = LoanEligibilityScore::updateOrCreate(
            ['user_id' => $user->id, 'chama_id' => $chama->id],
            [
                'score' => (int)$total_score,
                'rating' => $rating,
                'recommended_loan_limit' => $recommended_loan_limit,
                'recommended_interest_rate' => $recommended_interest_rate,
                'risk_level' => $risk_level,
                'contribution_score' => (int)$contribution_score,
                'repayment_score' => (int)$repayment_score,
                'attendance_score' => (int)$attendance_score,
                'communication_score' => (int)$communication_score,
                'default_history_score' => (int)$default_history_score,
                'factors' => [
                    'total_contributions' => $totalContributions,
                    'completed_loans' => $completed,
                    'defaulted_loans' => $defaulted,
                    'attendance_count' => $attendance,
                ],
                'calculated_at' => now(),
                'expires_at' => now()->addMonths(3),
            ]
        );

        return $score;
    }

    /**
     * Get all member scores for a Chama
     */
    public function indexForChama(Chama $chama)
    {
        $this->authorize('view', $chama);

        $scores = $chama->loanEligibilityScores()
                       ->with('user')
                       ->orderByDesc('score')
                       ->paginate(20);

        return response()->json($scores);
    }

    /**
     * Refresh score for a member
     */
    public function refresh(Chama $chama, User $user)
    {
        $this->authorize('manage-chama', $chama);

        $score = $this->calculateScore($chama, $user);

        return response()->json(['message' => 'Score recalculated', 'score' => $score]);
    }
}
