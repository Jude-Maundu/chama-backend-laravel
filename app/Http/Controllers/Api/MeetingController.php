<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Meeting;
use App\Models\Attendance;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MeetingController extends Controller
{
    public function __construct()
    {
        // Middleware handled in routes/api.php
    }

    public function index()
    {
        try {
            $chamaId = Auth::user()->current_chama_id;
            $upcoming = request('upcoming', false);
            $perPage = request('per_page', 10);
            
            $query = Meeting::where('chama_id', $chamaId);

            if ($upcoming) {
                $meetings = $query->upcoming()->paginate($perPage);
            } else {
                $meetings = $query->latest()->paginate($perPage);
            }
            
            return response()->json($meetings);
        } catch (\Exception $e) {
            \Log::error('MeetingController.index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch meetings', 'message' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        return response()->json([
            'success' => true,
            'message' => 'Use POST to create meetings'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'agenda' => 'required|string',
            'meeting_date' => 'required|date',
            'venue' => 'required|string',
            'duration' => 'nullable|integer',
            'type' => 'nullable|string',
            'notes' => 'nullable|string',
            'send_notifications' => 'nullable|boolean',
            'require_rsvp' => 'nullable|boolean',
            'virtual_link' => 'nullable|url',
        ]);

        $chamaId = Auth::user()->current_chama_id;

        $meeting = Meeting::create([
            'chama_id' => $chamaId,
            'title' => $request->title,
            'description' => $request->notes,
            'agenda' => $request->agenda,
            'meeting_date' => $request->meeting_date,
            'venue' => $request->venue,
            'virtual_link' => $request->virtual_link,
            'duration_minutes' => $request->duration ?? 120,
            'type' => $request->type ?? 'general',
            'status' => 'scheduled',
            'created_by' => Auth::id(),
            'send_notifications' => $request->send_notifications ?? true,
            'require_rsvp' => $request->require_rsvp ?? false,
        ]);

        // Log the action
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'table_name' => 'meetings',
            'record_id' => $meeting->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meeting scheduled successfully',
            'meeting' => $meeting->load('creator')
        ], 201);
    }

    public function show($id)
    {
        $meeting = Meeting::with(['creator', 'attendees'])->findOrFail($id);
        $attendance = Attendance::where('meeting_id', $id)->with('user')->get();

        return response()->json([
            'meeting' => $meeting,
            'attendance' => $attendance
        ]);
    }

    public function attendance($id)
    {
        $meeting = Meeting::findOrFail($id);
        $members = \App\Models\User::role('member')->where('is_active', true)->get();
        $attendance = Attendance::where('meeting_id', $id)->get()->keyBy('user_id');
        
        return response()->json([
            'success' => true,
            'meeting' => $meeting,
            'members' => $members,
            'attendance' => $attendance
        ]);
    }

    public function markAttendance(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,excused,late',
        ]);

        Attendance::updateOrCreate(
            ['meeting_id' => $id, 'user_id' => $request->user_id],
            [
                'status' => $request->status,
                'arrival_time' => $request->status === 'present' ? now() : null,
                'excuse_reason' => $request->excuse_reason,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function uploadMinutes(Request $request, $id)
    {
        $request->validate([
            'minutes' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $meeting = Meeting::findOrFail($id);

        $path = $request->file('minutes')->store('meeting-minutes', 'public');

        $meeting->update([
            'minutes_file' => $path,
            'status' => 'completed',
        ]);

        // Log the action
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'uploaded_minutes',
            'table_name' => 'meetings',
            'record_id' => $meeting->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Minutes uploaded successfully',
            'meeting' => $meeting
        ]);
    }

    public function vote(Request $request, $id)
    {
        $request->validate([
            'topic' => 'required|string',
            'vote_option' => 'required|string',
        ]);

        \App\Models\MeetingVote::create([
            'meeting_id' => $id,
            'user_id' => Auth::id(),
            'topic' => $request->topic,
            'vote_option' => $request->vote_option,
            'notes' => $request->notes,
        ]);

        return response()->json(['success' => true]);
    }

    public function virtual($id)
    {
        $meeting = Meeting::findOrFail($id);

        // Generate Zoom/Google Meet link if not exists
        if (!$meeting->virtual_link) {
            $meeting->virtual_link = $this->generateVirtualLink($meeting);
            $meeting->save();
        }

        return response()->json([
            'meeting' => $meeting,
            'virtual_link' => $meeting->virtual_link
        ]);
    }

    private function generateVirtualLink($meeting)
    {
        // Integrate with Zoom API or Google Meet API
        return 'https://meet.google.com/' . substr(md5($meeting->id . time()), 0, 10);
    }
}
