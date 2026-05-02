<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if (!$user->hasRole('member') && !$user->hasRole('admin') && !$user->hasRole('treasurer')) {
            abort(403, 'Member access required.');
        }

        return $next($request);
    }
}
