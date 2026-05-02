<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;

class CheckLoanEligibility
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        $minMonths = Setting::get('min_membership_months', 3);
        $joinedDate = $user->created_at;
        
        if ($joinedDate->diffInMonths(now()) < $minMonths) {
            return response()->json([
                'error' => "You must be a member for at least {$minMonths} months to apply for a loan."
            ], 403);
        }
        
        $hasDefaultedLoan = $user->loans()->where('status', 'defaulted')->exists();
            
        if ($hasDefaultedLoan) {
            return response()->json([
                'error' => 'You have defaulted loans. Please clear them before applying for a new loan.'
            ], 403);
        }
        
        $maxLoans = Setting::get('max_active_loans', 2);
        $activeLoans = $user->loans()->whereIn('status', ['approved', 'disbursed'])->count();
            
        if ($activeLoans >= $maxLoans) {
            return response()->json([
                'error' => "You already have {$activeLoans} active loan(s). Maximum allowed is {$maxLoans}."
            ], 403);
        }

        return $next($request);
    }
}
