<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingDecision;
use App\Models\MeetingVote;
use App\Models\Poll;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    public function index()
    {
        $meetings = Meeting::orderBy('scheduled_date', 'desc')->paginate(20);
        $upcomingCount = Meeting::where('scheduled_date', '>', now())->count();
        $completedCount = Meeting::where('scheduled_date', '<', now())->count();

        return view('meetings.index', compact('meetings', 'upcomingCount', 'completedCount'));
    }

    public function create()
    {
        return view('meetings.create');
    }

    public function store()
    {
        $data = request()->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date|after:now',
            'location' => 'required|string',
            'meeting_type' => 'required|in:AGM,Quarterly,Emergency,Special',
        ]);

        $data['created_by'] = Auth::id();
        $data['status'] = 'scheduled';

        Meeting::create($data);

        return redirect()->route('meetings.index')->with('success', 'Meeting created!');
    }

    public function show(Meeting $meeting)
    {
        $decisions = $meeting->decisions;
        $polls = $meeting->polls;
        $attendees = $meeting->attendees()->count();
        $votes = $meeting->votes;

        return view('meetings.show', compact('meeting', 'decisions', 'polls', 'attendees', 'votes'));
    }

    public function addDecision(Meeting $meeting)
    {
        $data = request()->validate([
            'description' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
        ]);

        $data['meeting_id'] = $meeting->id;
        $data['created_by'] = Auth::id();

        MeetingDecision::create($data);

        return redirect()->back()->with('success', 'Decision recorded!');
    }

    public function createPoll(Meeting $meeting)
    {
        $data = request()->validate([
            'question' => 'required|string',
            'options' => 'required|array|min:2',
            'poll_type' => 'required|in:anonymous,public',
        ]);

        $poll = Poll::create([
            'meeting_id' => $meeting->id,
            'question' => $data['question'],
            'poll_type' => $data['poll_type'],
            'created_by' => Auth::id(),
        ]);

        foreach ($data['options'] as $option) {
            $poll->options()->create(['option_text' => $option]);
        }

        return redirect()->back()->with('success', 'Poll created!');
    }

    public function votePoll(Poll $poll)
    {
        $data = request()->validate([
            'option_id' => 'required|exists:poll_options,id',
        ]);

        $existingVote = MeetingVote::where('poll_id', $poll->id)
                                    ->where('user_id', Auth::id())
                                    ->first();

        if ($existingVote) {
            return back()->withErrors(['error' => 'You have already voted!']);
        }

        MeetingVote::create([
            'poll_id' => $poll->id,
            'user_id' => Auth::id(),
            'option_id' => $data['option_id'],
        ]);

        return redirect()->back()->with('success', 'Vote recorded!');
    }

    public function markAttendance(Meeting $meeting)
    {
        $user = Auth::user();

        $meeting->attendees()->attach($user->id, ['status' => 'attended', 'arrival_time' => now()]);

        return redirect()->back()->with('success', 'Attendance marked!');
    }

    public function report(Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        $data = request()->validate([
            'summary' => 'required|string',
            'attendance_count' => 'required|integer|min:0',
        ]);

        $meeting->update([
            'summary' => $data['summary'],
            'attendance_count' => $data['attendance_count'],
            'status' => 'completed',
        ]);

        return redirect()->back()->with('success', 'Meeting report saved!');
    }
}
