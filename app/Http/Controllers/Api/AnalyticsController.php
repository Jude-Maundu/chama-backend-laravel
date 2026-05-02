<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\Loan;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Predictive Analytics (Feature 24)
     */
    public function predictive(Chama $chama)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'default_risk' => $this->calculateDefaultRisk($chama),
                'growth_trends' => $this->calculateGrowthTrends($chama),
                'churn_prediction' => $this->predictChurn($chama),
            ]
        ]);
    }

    private function calculateDefaultRisk(Chama $chama)
    {
        $activeLoans = Loan::where('chama_id', $chama->id)
                          ->where('status', 'disbursed')
                          ->get();
        
        $riskyLoans = 0;
        foreach ($activeLoans as $loan) {
            $overdueAmount = $loan->repayments()->where('due_date', '<', now())->where('status', 'pending')->sum('amount');
            if ($overdueAmount > 0) {
                $riskyLoans++;
            }
        }

        $totalActive = $activeLoans->count();
        $riskPercentage = $totalActive > 0 ? ($riskyLoans / $totalActive) * 100 : 0;

        return [
            'risk_level' => $riskPercentage > 20 ? 'high' : ($riskPercentage > 10 ? 'medium' : 'low'),
            'risk_percentage' => round($riskPercentage, 2),
            'risky_loans_count' => $riskyLoans,
            'total_active_loans' => $totalActive
        ];
    }

    private function calculateGrowthTrends(Chama $chama)
    {
        $monthlyContributions = Contribution::where('chama_id', $chama->id)
                                           ->where('status', 'completed')
                                           ->select(
                                               DB::raw('SUM(amount) as total'),
                                               DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as month")
                                           )
                                           ->groupBy('month')
                                           ->orderBy('month', 'desc')
                                           ->limit(6)
                                           ->get();

        return $monthlyContributions;
    }

    private function predictChurn(Chama $chama)
    {
        // Simple logic: users who haven't contributed in 3 months
        $inactiveUsers = DB::table('chama_members')
                          ->where('chama_id', $chama->id)
                          ->whereNotExists(function($query) {
                              $query->select(DB::raw(1))
                                    ->from('contributions')
                                    ->whereRaw('contributions.user_id = chama_members.user_id')
                                    ->where('payment_date', '>', now()->subMonths(3));
                          })
                          ->count();

        return [
            'at_risk_count' => $inactiveUsers,
            'recommendation' => $inactiveUsers > 0 ? 'Send engagement reminders' : 'Healthy engagement'
        ];
    }

    /**
     * Benchmarking (Feature 27)
     */
    public function benchmarking(Chama $chama)
    {
        $avgChamaBalance = Chama::avg('balance') ?? 0;
        $avgMemberCount = DB::table('chama_members')->count() / (Chama::count() ?: 1);

        return response()->json([
            'success' => true,
            'data' => [
                'your_chama' => [
                    'balance' => $chama->balance,
                    'members' => $chama->members()->count(),
                ],
                'industry_averages' => [
                    'balance' => round($avgChamaBalance, 2),
                    'members' => round($avgMemberCount, 1),
                ],
                'performance_percentile' => 75, // Mock data
            ]
        ]);
    }
}
