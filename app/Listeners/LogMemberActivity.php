<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;

class LogMemberActivity
{
    public function handle($event)
    {
        $user = null;
        $action = '';
        
        if ($event instanceof Login) {
            $user = $event->user;
            $action = 'login';
        } elseif ($event instanceof Logout) {
            $user = $event->user;
            $action = 'logout';
        } elseif ($event instanceof Registered) {
            $user = $event->user;
            $action = 'register';
        }
        
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
