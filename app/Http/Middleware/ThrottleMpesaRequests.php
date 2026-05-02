<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;

class ThrottleMpesaRequests
{
    public function handle(Request $request, Closure $next)
    {
        $limiter = app(RateLimiter::class);
        $key = 'mpesa_' . (Auth::id() ?? $request->ip());
        
        if ($limiter->tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => 'Too many M-Pesa requests. Please wait a moment.'
            ], 429);
        }
        
        $limiter->hit($key, 60);
        
        return $next($request);
    }
}
