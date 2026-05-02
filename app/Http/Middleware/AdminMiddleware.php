<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if (!Auth::user()->hasRole('chama-admin') && !Auth::user()->hasRole('super-admin')) {
            return response()->json(['error' => 'Admin access required.'], 403);
        }

        return $next($request);
    }
}
