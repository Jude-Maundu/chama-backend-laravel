<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->two_factor_enabled && !session('2fa_verified')) {
            return redirect()->route('verify.otp');
        }

        return $next($request);
    }
}