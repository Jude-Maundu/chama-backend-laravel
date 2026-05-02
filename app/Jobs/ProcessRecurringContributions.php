<?php

namespace App\Jobs;

use App\Models\RecurringContribution;
use App\Models\Contribution;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Carbon\Carbon;

class ProcessRecurringContributions implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $now = now();
        
        // Get all active recurring contributions that are due
        $dueContributions = RecurringContribution::where('status', 'active')
                                                ->whereNotNull('next_due_date')
                                                ->where('next_due_date', '<=', $now)
                                                ->get();

        foreach ($dueContributions as $recurring) {
            try {
                // Process payment via M-Pesa or other payment method
                $this->processContribution($recurring);
                
                // Calculate next due date
                $this->scheduleNextDue($recurring);
                
                $recurring->update([
                    'failed_attempts' => 0,
                    'failure_reason' => null,
                    'last_processed_at' => $now,
                ]);
            } catch (\Exception $e) {
                $this->handleFailedContribution($recurring, $e);
            }
        }
    }

    private function processContribution(RecurringContribution $recurring): void
    {
        // Create a contribution record
        Contribution::create([
            'chama_id' => $recurring->chama_id,
            'user_id' => $recurring->user_id,
            'amount' => $recurring->amount,
            'description' => 'Recurring contribution - ' . $recurring->frequency,
            'payment_method' => $recurring->payment_method,
            'status' => 'pending',
            'payment_reference' => $recurring->payment_reference,
        ]);

        // Trigger payment processing based on payment method
        match($recurring->payment_method) {
            'mpesa' => $this->processMpesaPayment($recurring),
            'airtel_money' => $this->processAirtelPayment($recurring),
            'tigo_pesa' => $this->processTigoPesaPayment($recurring),
            'bank_transfer' => $this->processBankTransfer($recurring),
            default => throw new \Exception('Unknown payment method'),
        };
    }

    private function processMpesaPayment(RecurringContribution $recurring): void
    {
        // Integrate with M-Pesa API
        // This is a placeholder - implement actual M-Pesa integration
        \Log::info('Processing M-Pesa payment for recurring contribution', [
            'recurring_id' => $recurring->id,
            'amount' => $recurring->amount,
        ]);
    }

    private function processAirtelPayment(RecurringContribution $recurring): void
    {
        // Integrate with Airtel Money API
        \Log::info('Processing Airtel Money payment for recurring contribution', [
            'recurring_id' => $recurring->id,
            'amount' => $recurring->amount,
        ]);
    }

    private function processTigoPesaPayment(RecurringContribution $recurring): void
    {
        // Integrate with Tigo Pesa API
        \Log::info('Processing Tigo Pesa payment for recurring contribution', [
            'recurring_id' => $recurring->id,
            'amount' => $recurring->amount,
        ]);
    }

    private function processBankTransfer(RecurringContribution $recurring): void
    {
        // Handle bank transfer - might require manual processing
        \Log::info('Bank transfer requested for recurring contribution', [
            'recurring_id' => $recurring->id,
            'amount' => $recurring->amount,
        ]);
    }

    private function scheduleNextDue(RecurringContribution $recurring): void
    {
        $nextDate = match($recurring->frequency) {
            'daily' => $recurring->next_due_date->addDay(),
            'weekly' => $recurring->next_due_date->addWeek(),
            'bi_weekly' => $recurring->next_due_date->addWeeks(2),
            'monthly' => $recurring->next_due_date->addMonth(),
            'quarterly' => $recurring->next_due_date->addQuarters(1),
            'yearly' => $recurring->next_due_date->addYear(),
            default => now()->addMonth(),
        };

        $recurring->update(['next_due_date' => $nextDate]);
    }

    private function handleFailedContribution(RecurringContribution $recurring, \Exception $e): void
    {
        $recurring->increment('failed_attempts');
        $recurring->update(['failure_reason' => $e->getMessage()]);

        // Cancel if too many failures
        if ($recurring->failed_attempts >= 5) {
            $recurring->update([
                'status' => 'failed',
            ]);

            // Notify member of cancellation
            \Log::warning('Recurring contribution cancelled due to repeated failures', [
                'recurring_id' => $recurring->id,
                'attempts' => $recurring->failed_attempts,
            ]);
        }
    }

    public function middleware(): array
    {
        return [new RateLimited('recurring-contributions')];
    }
}
