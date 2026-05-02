<?php

namespace App\Jobs;

use App\Models\BankReconciliation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ProcessBankReconciliation implements ShouldQueue
{
    use Queueable;

    public function __construct(private BankReconciliation $reconciliation) {}

    public function handle(): void
    {
        try {
            $filePath = $this->reconciliation->file_path;
            
            if (!Storage::exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            $content = Storage::get($filePath);
            
            match($this->reconciliation->file_type) {
                'csv' => $this->processCSV($content),
                'xlsx' => $this->processExcel($content),
                'pdf' => $this->processPDF($content),
                default => throw new \Exception('Unsupported file type'),
            };

            $this->reconciliation->update(['status' => 'processing']);
        } catch (\Exception $e) {
            \Log::error('Bank reconciliation processing failed', [
                'reconciliation_id' => $this->reconciliation->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->reconciliation->update(['status' => 'failed']);
        }
    }

    private function processCSV(string $content): void
    {
        $csv = Reader::createFromString($content);
        $csv->setHeaderOffset(0);
        
        $transactions = [];
        $matchedCount = 0;

        foreach ($csv->getRecords() as $record) {
            $transaction = [
                'date' => $record['Date'] ?? $record['date'] ?? null,
                'description' => $record['Description'] ?? $record['description'] ?? null,
                'amount' => (float)($record['Amount'] ?? $record['amount'] ?? 0),
                'reference' => $record['Reference'] ?? $record['reference'] ?? null,
            ];

            // Try to match with system transactions
            if ($this->matchTransaction($transaction)) {
                $matchedCount++;
            }

            $transactions[] = $transaction;
        }

        $this->reconciliation->update([
            'total_transactions' => count($transactions),
            'matched_transactions' => $matchedCount,
            'unmatched_transactions' => count($transactions) - $matchedCount,
            'status' => 'matched',
        ]);
    }

    private function processExcel(string $content): void
    {
        // Use Laravel Excel package
        \Log::info('Processing Excel file - requires Laravel Excel package');
    }

    private function processPDF(string $content): void
    {
        // Use PDF parsing library
        \Log::info('Processing PDF file - requires PDF parsing library');
    }

    private function matchTransaction(array $transaction): bool
    {
        $systemTransaction = \DB::table('transactions')
            ->where('chama_id', $this->reconciliation->chama_id)
            ->where('amount', $transaction['amount'])
            ->whereDate('created_at', $transaction['date'])
            ->first();

        return $systemTransaction !== null;
    }
}
