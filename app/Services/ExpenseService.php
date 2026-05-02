<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use App\Models\Chama;

class ExpenseService
{
    /**
     * Get budget summary
     */
    public function getBudgetSummary(Chama $chama)
    {
        $categories = $chama->expenseCategories()->get();
        
        $summary = [
            'total_budget' => 0,
            'total_spent' => 0,
            'remaining' => 0,
            'categories' => [],
        ];

        foreach ($categories as $category) {
            $spent = $category->getTotalExpenses();
            $budget = $category->budget_limit ?? 0;

            $summary['total_budget'] += $budget;
            $summary['total_spent'] += $spent;

            $summary['categories'][] = [
                'id' => $category->id,
                'name' => $category->name,
                'budget' => $budget,
                'spent' => $spent,
                'remaining' => $budget - $spent,
                'percentage' => $budget > 0 ? ($spent / $budget) * 100 : 0,
                'is_over_budget' => $spent > $budget,
            ];
        }

        $summary['remaining'] = $summary['total_budget'] - $summary['total_spent'];
        $summary['percentage_used'] = $summary['total_budget'] > 0 
            ? ($summary['total_spent'] / $summary['total_budget']) * 100 
            : 0;

        return $summary;
    }

    /**
     * Get budget alerts
     */
    public function getBudgetAlerts(Chama $chama): array
    {
        $alerts = [];
        $categories = $chama->expenseCategories()->get();

        foreach ($categories as $category) {
            if (!$category->budget_limit) {
                continue;
            }

            $spent = $category->getTotalExpenses();
            $remaining = $category->getRemainingBudget();
            $percentageUsed = ($spent / $category->budget_limit) * 100;

            if ($spent > $category->budget_limit) {
                $alerts[] = [
                    'type' => 'error',
                    'category' => $category->name,
                    'message' => 'Over budget by ' . abs($remaining),
                    'amount' => abs($remaining),
                ];
            } elseif ($percentageUsed >= 90) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => $category->name,
                    'message' => 'Budget usage at ' . round($percentageUsed, 1) . '%',
                    'amount' => $remaining,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get monthly expense trend
     */
    public function getMontlyTrend(Chama $chama, int $months = 6)
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $total = $chama->expenses()
                          ->where('status', 'approved')
                          ->whereMonth('expense_date', $date->month)
                          ->whereYear('expense_date', $date->year)
                          ->sum('amount');

            $trend[] = [
                'month' => $date->format('M Y'),
                'amount' => $total,
            ];
        }

        return $trend;
    }

    /**
     * Get top expense categories
     */
    public function getTopCategories(Chama $chama, int $limit = 5)
    {
        return $chama->expenses()
                    ->where('status', 'approved')
                    ->selectRaw('expense_category_id, SUM(amount) as total')
                    ->groupBy('expense_category_id')
                    ->orderByDesc('total')
                    ->limit($limit)
                    ->with('category')
                    ->get();
    }
}
