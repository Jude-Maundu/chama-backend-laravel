<?php

namespace App\Listeners;

use App\Events\LoanApproved;
use App\Models\Notification;
use App\Services\SmsService;

class SendLoanApprovalNotification
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(LoanApproved $event)
    {
        Notification::create([
            'user_id' => $event->member->id,
            'title' => 'Loan Approved',
            'message' => "Your loan application of " . number_format($event->amount, 2) . " has been approved.",
            'type' => 'loan',
            'channel' => 'in_app',
            'is_read' => false,
        ]);
    }
}
