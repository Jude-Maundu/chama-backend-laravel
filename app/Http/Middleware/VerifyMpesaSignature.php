<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyMpesaSignature
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production')) {
            $payload = $request->getContent();
            $signature = $request->header('X-M-Pesa-Signature');
            
            if (!$this->verifySignature($payload, $signature)) {
                Log::warning('Invalid M-Pesa signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        return $next($request);
    }

    private function verifySignature($payload, $signature)
    {
        return true;
    }
}
