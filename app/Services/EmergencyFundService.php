<?php

namespace App\Services;

use App\Models\EmergencyFund;
use App\Models\Chama;
use Illuminate\Pagination\Paginator;

class EmergencyFundService
{
    /**
     * Initialize or update emergency fund
     */
    public function initializeOrUpdate(Chama $chama, array $data): EmergencyFund
    {
        return EmergencyFund::updateOrCreate(
            ['chama_id' => $chama->id],
            $data
        );
    }

    /**
     * Get fund health status
     */
    public function getHealthStatus(EmergencyFund $fund): string
    {
        $percentage = ($fund->total_balance / $fund->minimum_balance) * 100;

        return match(true) {
            $percentage >= 100 => 'healthy',
            $percentage >= 75 => 'good',
            $percentage >= 50 => 'warning',
            default => 'critical',
        };
    }

    /**
     * Get health color for UI
     */
    public function getHealthColor(EmergencyFund $fund): string
    {
        return match($this->getHealthStatus($fund)) {
            'healthy' => '#10b981',
            'good' => '#3b82f6',
            'warning' => '#f59e0b',
            'critical' => '#ef4444',
            default => '#6b7280',
        };
    }

    /**
     * Get additional funds needed to reach minimum
     */
    public function getFundsNeeded(EmergencyFund $fund): float
    {
        return max(0, $fund->minimum_balance - $fund->total_balance);
    }

    /**
     * Calculate days to reach minimum balance
     */
    public function daysToHealthy(EmergencyFund $fund, float $monthlyContribution): ?int
    {
        if ($monthlyContribution <= 0) {
            return null;
        }

        $fundsNeeded = $this->getFundsNeeded($fund);
        if ($fundsNeeded <= 0) {
            return 0;
        }

        $monthsNeeded = $fundsNeeded / $monthlyContribution;
        return (int)ceil($monthsNeeded * 30);
    }

    /**
     * Get pending withdrawal requests
     */
    public function getPendingWithdrawals(EmergencyFund $fund)
    {
        return $fund->withdrawals()
                   ->where('status', 'pending')
                   ->with('user')
                   ->orderBy('created_at', 'desc')
                   ->get();
    }

    /**
     * Get withdrawal history
     */
    public function getWithdrawalHistory(EmergencyFund $fund, int $limit = 20)
    {
        return $fund->withdrawals()
                   ->where('status', 'processed')
                   ->with('user')
                   ->orderBy('processed_at', 'desc')
                   ->limit($limit)
                   ->get();
    }
}
