<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreasurerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if (!$user->hasRole('treasurer') && !$user->hasRole('admin')) {
            abort(403, 'Treasurer access required.');
        }

        return $next($request);
    }
}
