<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $recipients;
    protected $message;

    public function __construct(array $recipients, string $message)
    {
        $this->recipients = $recipients;
        $this->message = $message;
    }

    public function handle(SmsService $smsService)
    {
        foreach ($this->recipients as $recipient) {
            $smsService->send($recipient, $this->message);
        }
    }
}
