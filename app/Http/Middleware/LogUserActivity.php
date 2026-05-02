<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (Auth::check() && $this->shouldLog($request)) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    private function shouldLog($request)
    {
        $excludePaths = ['livewire', 'telescope', 'horizon', 'debugbar'];
        
        foreach ($excludePaths as $path) {
            if (str_contains($request->path(), $path)) {
                return false;
            }
        }
        
        return true;
    }
}
