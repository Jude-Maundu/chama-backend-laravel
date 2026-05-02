<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Notification;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);
            
        $unreadCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
            
        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unreadCount' => $unreadCount
            ]
        ]);
    }

    public function preferences()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => [
                'email_notifications' => $user->email_notifications ?? true,
                'sms_notifications' => $user->sms_notifications ?? true,
                'push_notifications' => $user->push_notifications ?? true,
            ]
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        $user = Auth::user();
        $user->update($request->only([
            'email_notifications',
            'sms_notifications',
            'push_notifications'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully'
        ]);
    }

    public function sendForm()
    {
        $members = User::role('member')->where('is_active', true)->get();
        return response()->json([
            'success' => true,
            'data' => ['members' => $members]
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'recipients' => 'required|array',
            'channel' => 'required|array|min:1',
        ]);

        $recipients = User::whereIn('id', $request->recipients)->get();
        
        foreach ($recipients as $recipient) {
            // Save in-app notification
            if (in_array('in_app', $request->channel)) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type ?? 'system',
                    'channel' => 'in_app',
                    'is_read' => false,
                ]);
            }
            
            // Send SMS
            if (in_array('sms', $request->channel) && $recipient->phone) {
                $this->smsService->send($recipient->phone, $request->message);
            }
            
            // Send Email
            if (in_array('email', $request->channel) && $recipient->email) {
                Mail::raw($request->message, function ($mail) use ($recipient, $request) {
                    $mail->to($recipient->email)
                        ->subject($request->title);
                });
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications sent successfully'
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();
            
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
            
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function smsSettings()
    {
        $settings = [
            'api_key' => config('services.africastalking.api_key'),
            'username' => config('services.africastalking.username'),
            'sender_id' => config('services.africastalking.sender_id'),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateSmsSettings(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'username' => 'required|string',
            'sender_id' => 'required|string',
        ]);
        
        // Update settings in database
        \App\Models\Setting::set('at_api_key', $request->api_key);
        \App\Models\Setting::set('at_username', $request->username);
        \App\Models\Setting::set('at_sender_id', $request->sender_id);
        
        return response()->json([
            'success' => true,
            'message' => 'SMS settings updated successfully'
        ]);
    }

    public function reminderSettings()
    {
        $reminders = [
            'contribution_reminder_days' => \App\Models\Setting::get('contribution_reminder_days', 3),
            'meeting_reminder_days' => \App\Models\Setting::get('meeting_reminder_days', 1),
            'loan_repayment_reminder_days' => \App\Models\Setting::get('loan_repayment_reminder_days', 2),
            'send_contribution_reminders' => \App\Models\Setting::get('send_contribution_reminders', true),
            'send_meeting_reminders' => \App\Models\Setting::get('send_meeting_reminders', true),
            'send_loan_reminders' => \App\Models\Setting::get('send_loan_reminders', true),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $reminders
        ]);
    }

    public function updateReminders(Request $request)
    {
        $request->validate([
            'contribution_reminder_days' => 'integer|min:1|max:30',
            'meeting_reminder_days' => 'integer|min:1|max:7',
            'loan_repayment_reminder_days' => 'integer|min:1|max:30',
        ]);
        
        foreach ($request->all() as $key => $value) {
            \App\Models\Setting::set($key, $value);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Reminder settings updated successfully'
        ]);
    }
}