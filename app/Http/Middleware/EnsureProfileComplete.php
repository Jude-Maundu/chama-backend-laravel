<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user->profile) {
            return redirect()->route('profile.edit')->with('warning', 'Please complete your profile first.');
        }
        
        $requiredFields = ['national_id', 'emergency_contact_name', 'emergency_contact_phone'];
        $profile = $user->profile;
        
        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                return redirect()->route('profile.edit')->with('warning', "Please update your {$field}.");
            }
        }
        
        return $next($request);
    }
}
