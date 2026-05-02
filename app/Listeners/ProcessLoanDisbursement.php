<?php

namespace App\Listeners;

use App\Events\LoanApproved;
use App\Services\MpesaService;

class ProcessLoanDisbursement
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    public function handle(LoanApproved $event)
    {
        // Process M-Pesa disbursement
        $this->mpesaService->b2c(
            $event->member->phone,
            $event->amount,
            "Loan Disbursement - Loan #{$event->loan->id}"
        );
    }
}
