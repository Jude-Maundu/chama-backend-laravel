<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::where('date', '>=', now()->startOfDay())
                      ->orderBy('date')
                      ->paginate(20);
        $upcomingCount = Event::where('date', '>=', now())->count();
        $attendingCount = Auth::user()->events()->count();

        return view('events.index', compact('events', 'upcomingCount', 'attendingCount'));
    }

    public function create()
    {
        return view('events.create');
    }

    public function store()
    {
        $data = request()->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'date' => 'required|date|after:now',
            'location' => 'required|string',
            'event_type' => 'required|in:Social,Training,Team Building,Fundraiser',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $data['created_by'] = Auth::id();
        $data['status'] = 'scheduled';

        Event::create($data);

        return redirect()->route('events.index')->with('success', 'Event created!');
    }

    public function show(Event $event)
    {
        $attendees = $event->attendees()->count();
        $rsvps = EventAttendee::where('event_id', $event->id)
                               ->selectRaw('response, COUNT(*) as count')
                               ->groupBy('response')
                               ->get();

        $userResponse = EventAttendee::where('event_id', $event->id)
                                     ->where('user_id', Auth::id())
                                     ->first();

        return view('events.show', compact('event', 'attendees', 'rsvps', 'userResponse'));
    }

    public function rsvp(Event $event)
    {
        $data = request()->validate([
            'response' => 'required|in:attending,not_attending,maybe',
        ]);

        EventAttendee::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => Auth::id()],
            ['response' => $data['response']]
        );

        return redirect()->back()->with('success', 'RSVP updated!');
    }

    public function markAttendance($eventId, $userId)
    {
        $this->authorize('update', Event::find($eventId));

        EventAttendee::where('event_id', $eventId)
                     ->where('user_id', $userId)
                     ->update(['attended' => true, 'attendance_time' => now()]);

        return redirect()->back()->with('success', 'Attendance marked!');
    }
}
