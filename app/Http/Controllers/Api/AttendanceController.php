<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Meeting;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceController extends Controller
{
    public function index($meetingId)
    {
        $meeting = Meeting::findOrFail($meetingId);
        $attendees = Attendance::where('meeting_id', $meetingId)
            ->with('user')
            ->get();
            
        $stats = [
            'total' => $attendees->count(),
            'present' => $attendees->where('status', 'present')->count(),
            'absent' => $attendees->where('status', 'absent')->count(),
            'excused' => $attendees->where('status', 'excused')->count(),
            'late' => $attendees->where('status', 'late')->count(),
            'attendance_rate' => $attendees->count() > 0 
                ? ($attendees->where('status', 'present')->count() / $attendees->count()) * 100 
                : 0,
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'meeting' => $meeting,
                'attendees' => $attendees,
                'stats' => $stats
            ]
        ]);
    }

    public function mark(Request $request, $meetingId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,excused,late',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['meeting_id' => $meetingId, 'user_id' => $request->user_id],
            [
                'status' => $request->status,
                'arrival_time' => $request->status === 'present' ? now() : null,
                'excuse_reason' => $request->excuse_reason,
                'signed_minutes' => $request->has('signed_minutes'),
            ]
        );

        if ($request->ajax()) {
            return response()->json(['success' => true, 'attendance' => $attendance]);
        }
        
        return redirect()->back()->with('success', 'Attendance marked successfully');
    }

    public function bulkMark(Request $request, $meetingId)
    {
        $request->validate([
            'attendees' => 'required|array',
            'attendees.*.user_id' => 'required|exists:users,id',
            'attendees.*.status' => 'required|in:present,absent,excused,late',
        ]);

        foreach ($request->attendees as $attendee) {
            Attendance::updateOrCreate(
                ['meeting_id' => $meetingId, 'user_id' => $attendee['user_id']],
                [
                    'status' => $attendee['status'],
                    'arrival_time' => $attendee['status'] === 'present' ? now() : null,
                    'excuse_reason' => $attendee['excuse_reason'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Bulk attendance marked successfully');
    }

    public function qrCode($meetingId)
    {
        $meeting = Meeting::findOrFail($meetingId);
        $url = route('attendance.scan', ['meeting' => $meetingId, 'user' => Auth::id()]);
        
        $qrCode = QrCode::size(300)->generate($url);
        
        return response()->json([
            'success' => true,
            'data' => [
                'meeting' => $meeting,
                'qrCode' => $qrCode
            ]
        ]);
    }

    public function scan(Request $request, $meetingId)
    {
        $user = Auth::user();
        
        $attendance = Attendance::updateOrCreate(
            ['meeting_id' => $meetingId, 'user_id' => $user->id],
            [
                'status' => 'present',
                'arrival_time' => now(),
            ]
        );
        
        return response()->json(['success' => true, 'message' => 'Attendance recorded']);
    }

    public function report($meetingId)
    {
        $meeting = Meeting::findOrFail($meetingId);
        $attendance = Attendance::where('meeting_id', $meetingId)
            ->with('user')
            ->get();
            
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('meetings.attendance-pdf', compact('meeting', 'attendance'));
        return $pdf->download('attendance-' . $meeting->id . '.pdf');
    }
}