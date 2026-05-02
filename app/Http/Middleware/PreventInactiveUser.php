<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreventInactiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        return $next($request);
    }
}
