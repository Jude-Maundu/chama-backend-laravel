<?php

namespace App\Jobs;

use App\Models\MpesaTransaction;
use App\Models\Contribution;
use App\Services\MpesaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMpesaPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $mpesaTransaction;

    public function __construct(MpesaTransaction $mpesaTransaction)
    {
        $this->mpesaTransaction = $mpesaTransaction;
    }

    public function handle(MpesaService $mpesaService)
    {
        $status = $mpesaService->queryStatus($this->mpesaTransaction->checkout_request_id);
        
        if ($status && $status['ResultCode'] == 0) {
            $this->mpesaTransaction->update([
                'status' => 'completed',
                'mpesa_receipt_number' => $status['ReceiptNumber'] ?? null,
            ]);
            
            if ($this->mpesaTransaction->reference_type === 'contribution') {
                Contribution::where('id', $this->mpesaTransaction->reference_id)
                    ->update(['status' => 'completed']);
            }
        }
    }
}
