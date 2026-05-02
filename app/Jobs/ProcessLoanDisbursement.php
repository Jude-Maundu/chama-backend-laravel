<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Services\MpesaService;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLoanDisbursement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $loan;

    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
    }

    public function handle(MpesaService $mpesaService)
    {
        $response = $mpesaService->b2c(
            $this->loan->user->phone,
            $this->loan->amount,
            "Loan Disbursement"
        );
        
        if ($response && isset($response['ConversationID'])) {
            $this->loan->update([
                'status' => 'disbursed',
                'disbursement_date' => now(),
                'mpesa_transaction_id' => $response['ConversationID'],
            ]);
            
            Transaction::create([
                'user_id' => $this->loan->user_id,
                'type' => 'loan_disbursement',
                'direction' => 'debit',
                'amount' => $this->loan->amount,
                'description' => 'Loan disbursement',
                'transaction_date' => now(),
                'created_by' => 1,
            ]);
        }
    }
}
