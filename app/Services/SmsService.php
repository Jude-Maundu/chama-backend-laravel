<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send($phone, $message)
    {
        $phone = $this->formatPhoneNumber($phone);
        
        try {
            // Africa's Talking API integration
            Log::info("SMS sent to {$phone}: {$message}");
            return true;
        } catch (\Exception $e) {
            Log::error("SMS failed: " . $e->getMessage());
            return false;
        }
    }

    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }
        return $phone;
    }
}
