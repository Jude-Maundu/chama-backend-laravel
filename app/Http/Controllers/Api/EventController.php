<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display a listing of events for a chama.
     */
    public function index(Chama $chama)
    {
        $events = Event::where('chama_id', $chama->id)
                      ->with(['creator'])
                      ->withCount('attendees')
                      ->orderByDesc('start_date')
                      ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'event_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string',
            'venue_url' => 'nullable|url',
            'ticket_price' => 'nullable|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'agenda' => 'nullable|string',
        ]);

        $event = Event::create(array_merge($validated, [
            'chama_id' => $chama->id,
            'created_by' => Auth::id(),
            'status' => 'scheduled'
        ]));

        return response()->json([
            'success' => true,
            'data' => $event
        ], 201);
    }

    /**
     * Display the specified event.
     */
    public function show(Chama $chama, Event $event)
    {
        $event->load(['creator', 'attendees.user']);
        $user_rsvp = $event->attendees()->where('user_id', Auth::id())->first();

        return response()->json([
            'success' => true,
            'data' => $event,
            'user_rsvp' => $user_rsvp
        ]);
    }

    /**
     * RSVP to an event.
     */
    public function rsvp(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:going,maybe,not_going',
            'number_of_guests' => 'nullable|integer|min:0',
        ]);

        $attendee = EventAttendee::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => Auth::id()],
            [
                'rsvp_status' => $request->status,
                'number_of_guests' => $request->number_of_guests ?? 0,
                'rsvp_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $attendee
        ]);
    }

    /**
     * Mark attendance (Admin).
     */
    public function markAttendance(Request $request, Event $event, EventAttendee $attendee)
    {
        $request->validate([
            'attended' => 'required|boolean',
        ]);

        $attendee->update([
            'attended' => $request->attended,
            'arrival_time' => $request->attended ? now() : null
        ]);

        return response()->json(['success' => true]);
    }
}
