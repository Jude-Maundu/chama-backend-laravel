<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralConversion;
use App\Models\MemberMilestone;
use App\Models\GroupChallenge;
use App\Models\Feedback;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

use App\Models\MemberProfile;
use App\Models\MemberSkill;

class MemberEngagementController extends Controller
{
    /**
     * Member Directory & Networking (Feature 11)
     */
    public function getDirectory(Request $request, Chama $chama)
    {
        $query = MemberProfile::where('chama_id', $chama->id)
                             ->where('show_profile_publicly', true)
                             ->with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('profession', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('skills', 'like', "%{$search}%")
                  ->orWhereHas('user', function($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('skill')) {
            $query->whereJsonContains('skills', $request->skill);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    public function getMyProfile(Chama $chama)
    {
        $profile = MemberProfile::firstOrCreate(
            ['user_id' => Auth::id(), 'chama_id' => $chama->id],
            ['show_profile_publicly' => true]
        );

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    public function updateProfile(Request $request, Chama $chama)
    {
        $profile = MemberProfile::where('user_id', Auth::id())
                                ->where('chama_id', $chama->id)
                                ->firstOrFail();

        $validated = $request->validate([
            'bio' => 'nullable|string',
            'profession' => 'nullable|string',
            'company' => 'nullable|string',
            'website' => 'nullable|url',
            'location' => 'nullable|string',
            'skills' => 'nullable|array',
            'interests' => 'nullable|array',
            'linkedin' => 'nullable|string',
            'twitter' => 'nullable|string',
            'instagram' => 'nullable|string',
            'show_contact_info' => 'boolean',
            'show_profile_publicly' => 'boolean',
        ]);

        $profile->update($validated);

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    /**
     * Member Skills Database (Feature 18)
     */
    public function getSkills(Chama $chama)
    {
        $skills = MemberSkill::where('chama_id', $chama->id)
                            ->with('user')
                            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $skills
        ]);
    }

    public function addSkill(Request $request, Chama $chama)
    {
        $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,expert',
            'years_experience' => 'nullable|integer|min:0',
        ]);

        $skill = MemberSkill::updateOrCreate(
            ['user_id' => Auth::id(), 'chama_id' => $chama->id, 'skill_name' => $request->skill_name],
            [
                'proficiency_level' => $request->proficiency_level,
                'years_experience' => $request->years_experience,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $skill
        ]);
    }

    /**
     * Referrals
     */
    public function getReferrals(Chama $chama)
    {
        $referral = Referral::firstOrCreate(
            ['referrer_id' => Auth::id(), 'chama_id' => $chama->id],
            [
                'referral_code' => Str::upper(Str::random(8)),
                'referral_link' => config('app.frontend_url') . '/join?code=' . Str::random(12),
                'status' => 'active'
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $referral->load('conversions.referredUser')
        ]);
    }

    public function inviteByEmail(Request $request, Chama $chama)
    {
        $request->validate(['email' => 'required|email']);
        
        $referral = Referral::where('referrer_id', Auth::id())
                          ->where('chama_id', $chama->id)
                          ->firstOrFail();

        $emails = $referral->referred_emails ?? [];
        if (!in_array($request->email, $emails)) {
            $emails[] = $request->email;
            $referral->update(['referred_emails' => $emails]);
            
            // Send email logic would go here
        }

        return response()->json(['success' => true, 'message' => 'Invitation sent']);
    }

    /**
     * Milestones
     */
    public function getMilestones(Chama $chama)
    {
        $milestones = MemberMilestone::where('user_id', Auth::id())
                                   ->where('chama_id', $chama->id)
                                   ->orderByDesc('milestone_date')
                                   ->get();

        return response()->json([
            'success' => true,
            'data' => $milestones
        ]);
    }

    /**
     * Challenges
     */
    public function getChallenges(Chama $chama)
    {
        $challenges = GroupChallenge::where('chama_id', $chama->id)
                                  ->whereIn('status', ['active', 'completed'])
                                  ->orderByDesc('start_date')
                                  ->get();

        return response()->json([
            'success' => true,
            'data' => $challenges
        ]);
    }

    public function joinChallenge(Chama $chama, GroupChallenge $challenge)
    {
        $participants = $challenge->participants ?? [];
        
        if (!isset($participants[Auth::id()])) {
            $participants[Auth::id()] = [
                'joined_at' => now(),
                'progress' => 0,
                'status' => 'active'
            ];
            $challenge->update(['participants' => $participants]);
        }

        return response()->json(['success' => true, 'message' => 'Joined challenge']);
    }

    /**
     * Feedback
     */
    public function getFeedback(Chama $chama)
    {
        $feedback = Feedback::where('chama_id', $chama->id)
                          ->with('user')
                          ->orderByDesc('created_at')
                          ->get()
                          ->map(function($item) {
                              if ($item->is_anonymous) {
                                  $item->user = null;
                              }
                              return $item;
                          });

        return response()->json([
            'success' => true,
            'data' => $feedback
        ]);
    }

    public function submitFeedback(Request $request, Chama $chama)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_anonymous' => 'boolean'
        ]);

        $feedback = Feedback::create([
            'chama_id' => $chama->id,
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'is_anonymous' => $request->is_anonymous ?? true,
            'status' => 'submitted'
        ]);

        return response()->json([
            'success' => true,
            'data' => $feedback
        ], 201);
    }

    public function voteFeedback(Request $request, Feedback $feedback)
    {
        $request->validate(['type' => 'required|in:up,down']);
        
        if ($request->type === 'up') {
            $feedback->increment('upvotes');
        } else {
            $feedback->increment('downvotes');
        }

        return response()->json(['success' => true]);
    }
}
