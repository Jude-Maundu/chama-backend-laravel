<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaWebhookMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Verify M-Pesa webhook signature
        $signature = $request->header('X-M-Pesa-Signature');
        $payload = $request->getContent();
        
        // Log incoming webhook for debugging
        Log::channel('mpesa')->info('M-Pesa Webhook Received', [
            'signature' => $signature,
            'payload' => json_decode($payload, true)
        ]);
        
        // Verify IP is from Safaricom (production only)
        $allowedIps = ['52.0.0.0/8', '54.0.0.0/8'];
        $clientIp = $request->ip();
        
        if (app()->environment('production') && !$this->ipInRange($clientIp, $allowedIps)) {
            Log::channel('mpesa')->warning('Unauthorized M-Pesa webhook attempt', ['ip' => $clientIp]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    private function ipInRange($ip, $ranges)
    {
        foreach ($ranges as $range) {
            if ($this->ipInCidr($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private function ipInCidr($ip, $cidr)
    {
        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
    }
}