<?php

namespace App\Console\Commands;

use App\Models\MpesaTransaction;
use App\Models\Contribution;
use App\Models\Transaction as LedgerTransaction;
use App\Services\MpesaService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncMpesaTransactions extends Command
{
    protected $signature = 'chama:sync-mpesa-transactions
                            {--days=7 : Number of days to sync}
                            {--fix : Fix mismatched transactions}';
    
    protected $description = 'Sync M-Pesa transactions and reconcile with system records';

    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        parent::__construct();
        $this->mpesaService = $mpesaService;
    }

    public function handle()
    {
        $this->info('🔄 Syncing M-Pesa transactions...');
        
        $days = (int) $this->option('days');
        $startDate = Carbon::now()->subDays($days);
        
        // Get pending M-Pesa transactions
        $pendingTransactions = MpesaTransaction::where('status', 'pending')
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $this->info("📋 Found {$pendingTransactions->count()} pending transaction(s)");
        
        $fixedCount = 0;
        
        foreach ($pendingTransactions as $mpesaTx) {
            $this->line("   • Checking: {$mpesaTx->checkout_request_id}");
            
            // Query status from M-Pesa
            $status = $this->mpesaService->queryStatus($mpesaTx->checkout_request_id);
            
            if ($status && isset($status['ResultCode'])) {
                if ($status['ResultCode'] == 0) {
                    // Transaction successful
                    $mpesaTx->update([
                        'status' => 'completed',
                        'mpesa_receipt_number' => $status['ReceiptNumber'] ?? null,
                        'result_code' => $status['ResultCode'],
                        'result_description' => $status['ResultDesc'],
                    ]);
                    
                    // Update related contribution if exists
                    if ($mpesaTx->reference_type === 'contribution') {
                        $contribution = Contribution::where('id', $mpesaTx->reference_id)->first();
                        if ($contribution && $contribution->status === 'pending') {
                            $contribution->update([
                                'status' => 'completed',
                                'mpesa_receipt' => $mpesaTx->mpesa_receipt_number,
                            ]);
                            
                            LedgerTransaction::create([
                                'user_id' => $contribution->user_id,
                                'type' => 'contribution',
                                'direction' => 'credit',
                                'amount' => $contribution->total_amount,
                                'description' => 'M-Pesa Contribution (Synced)',
                                'transaction_date' => now(),
                                'created_by' => 1,
                            ]);
                        }
                    }
                    
                    $fixedCount++;
                    $this->line("      ✅ Completed");
                } elseif ($status['ResultCode'] != 1037) { // Not pending
                    $mpesaTx->update([
                        'status' => 'failed',
                        'result_code' => $status['ResultCode'],
                        'result_description' => $status['ResultDesc'],
                    ]);
                    $this->line("      ❌ Failed: {$status['ResultDesc']}");
                }
            }
        }
        
        $this->newLine();
        $this->info("✅ Synced and fixed {$fixedCount} transaction(s)");
        
        return Command::SUCCESS;
    }
}