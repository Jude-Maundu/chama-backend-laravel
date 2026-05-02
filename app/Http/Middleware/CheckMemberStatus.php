<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckMemberStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account is inactive. Please contact administrator.');
        }

        return $next($request);
    }
}
