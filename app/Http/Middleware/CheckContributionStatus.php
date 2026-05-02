<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;

class CheckContributionStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        $maxOverdueMonths = Setting::get('max_overdue_months', 2);
        $cutoffDate = now()->subMonths($maxOverdueMonths);
        
        $hasOverdue = $user->contributions()
            ->where('status', 'pending')
            ->where('due_date', '<', $cutoffDate)
            ->exists();
            
        if ($hasOverdue && !$user->hasRole('admin')) {
            return response()->json([
                'error' => "You have overdue contributions. Please clear them to access this feature."
            ], 403);
        }

        return $next($request);
    }
}
