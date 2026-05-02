<?php

namespace App\Services;

use App\Models\RecurringContribution;
use App\Models\Contribution;
use Illuminate\Database\Eloquent\Collection;

class RecurringContributionService
{
    /**
     * Create a recurring contribution
     */
    public function create(array $data): RecurringContribution
    {
        return RecurringContribution::create($data);
    }

    /**
     * Get next due date based on frequency
     */
    public function getNextDueDate(string $frequency, $startDate)
    {
        return match($frequency) {
            'daily' => now()->parse($startDate)->addDay(),
            'weekly' => now()->parse($startDate)->addWeek(),
            'bi_weekly' => now()->parse($startDate)->addWeeks(2),
            'monthly' => now()->parse($startDate)->addMonth(),
            'quarterly' => now()->parse($startDate)->addQuarters(1),
            'yearly' => now()->parse($startDate)->addYear(),
            default => now()->parse($startDate)->addMonth(),
        };
    }

    /**
     * Get pending contributions
     */
    public function getPending(): Collection
    {
        return RecurringContribution::where('status', 'active')
                                   ->where('next_due_date', '<=', now())
                                   ->get();
    }

    /**
     * Calculate frequency display name
     */
    public function getFrequencyLabel(string $frequency): string
    {
        return match($frequency) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => 'Unknown',
        };
    }

    /**
     * Get estimated annual contribution amount
     */
    public function getEstimatedAnnual(RecurringContribution $contribution): float
    {
        return match($contribution->frequency) {
            'daily' => $contribution->amount * 365,
            'weekly' => $contribution->amount * 52,
            'bi_weekly' => $contribution->amount * 26,
            'monthly' => $contribution->amount * 12,
            'quarterly' => $contribution->amount * 4,
            'yearly' => $contribution->amount,
            default => 0,
        };
    }
}
