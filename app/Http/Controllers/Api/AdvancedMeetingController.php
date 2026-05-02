<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\Meeting;
use App\Models\MeetingDecision;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\MeetingTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvancedMeetingController extends Controller
{
    /**
     * Meeting Decisions (Feature 20)
     */
    public function getDecisions(Meeting $meeting)
    {
        $decisions = MeetingDecision::where('meeting_id', $meeting->id)
                                   ->with('responsiblePerson')
                                   ->get();
        return response()->json(['success' => true, 'data' => $decisions]);
    }

    public function storeDecision(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'decision_text' => 'required|string',
            'responsible_person_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
        ]);

        $decision = MeetingDecision::create(array_merge($validated, [
            'meeting_id' => $meeting->id,
            'status' => 'pending'
        ]));

        return response()->json(['success' => true, 'data' => $decision], 201);
    }

    public function updateDecision(Request $request, MeetingDecision $decision)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'completion_notes' => 'nullable|string',
        ]);

        $decision->update(array_merge($validated, [
            'completed_at' => $request->status === 'completed' ? now() : null
        ]));

        return response()->json(['success' => true, 'data' => $decision]);
    }

    /**
     * Digital Voting / Polls (Feature 21)
     */
    public function getPolls(Meeting $meeting)
    {
        $polls = Poll::where('meeting_id', $meeting->id)
                     ->with(['votes'])
                     ->get()
                     ->map(function($poll) {
                         $poll->user_voted = $poll->votes()->where('user_id', Auth::id())->exists();
                         return $poll;
                     });
        return response()->json(['success' => true, 'data' => $polls]);
    }

    public function storePoll(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'options' => 'required|array|min:2',
            'is_anonymous' => 'boolean',
            'is_weighted' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $poll = Poll::create(array_merge($validated, [
            'meeting_id' => $meeting->id,
            'status' => 'active',
            'created_by' => Auth::id()
        ]));

        return response()->json(['success' => true, 'data' => $poll], 201);
    }

    public function votePoll(Request $request, Poll $poll)
    {
        $request->validate([
            'option_index' => 'required|integer|min:0',
        ]);

        if ($poll->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Poll is not active'], 400);
        }

        if ($poll->votes()->where('user_id', Auth::id())->exists()) {
            return response()->json(['success' => false, 'message' => 'You have already voted'], 400);
        }

        $weight = 1;
        if ($poll->is_weighted) {
            // Logic for weighted voting based on shares/contributions
            $totalContributions = \App\Models\Contribution::where('user_id', Auth::id())
                                                         ->where('chama_id', $poll->meeting->chama_id)
                                                         ->where('status', 'completed')
                                                         ->sum('amount');
            $weight = $totalContributions > 0 ? $totalContributions : 1;
        }

        $vote = PollVote::create([
            'poll_id' => $poll->id,
            'user_id' => Auth::id(),
            'option_index' => $request->option_index,
            'weight' => $weight
        ]);

        return response()->json(['success' => true, 'data' => $vote], 201);
    }

    /**
     * Meeting Templates (Feature 22)
     */
    public function getTemplates(Chama $chama)
    {
        $templates = MeetingTemplate::where('chama_id', $chama->id)
                                   ->orWhereNull('chama_id') // System templates
                                   ->get();
        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function storeTemplate(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_agenda' => 'required|array',
            'default_type' => 'required|string',
        ]);

        $template = MeetingTemplate::create(array_merge($validated, [
            'chama_id' => $chama->id
        ]));

        return response()->json(['success' => true, 'data' => $template], 201);
    }
}
