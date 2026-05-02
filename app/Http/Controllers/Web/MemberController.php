<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MemberProfile;
use App\Models\MemberSkill;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    public function index()
    {
        $query = User::where('role', 'member');

        if (request('search')) {
            $search = request('search');
            $query->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
        }

        $members = $query->with('profile', 'skills')->paginate(20);
        $totalMembers = User::where('role', 'member')->count();
        $activeMembers = User::where('role', 'member')->where('is_active', true)->count();

        return view('members.index', compact('members', 'totalMembers', 'activeMembers'));
    }

    public function show(User $member)
    {
        $this->authorize('view', $member);

        $profile = $member->profile;
        $skills = $member->skills;
        $contributions = $member->contributions()->sum('total_amount');
        $loans = $member->loans()->count();
        $referrals = $member->referredMembers()->count();

        return view('members.show', compact('member', 'profile', 'skills', 'contributions', 'loans', 'referrals'));
    }

    public function editProfile()
    {
        $user = Auth::user();
        $profile = $user->profile;

        return view('members.edit-profile', compact('user', 'profile'));
    }

    public function updateProfile()
    {
        $user = Auth::user();

        $data = request()->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'bio' => 'nullable|string',
            'business_name' => 'nullable|string',
            'business_description' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $user->update(['name' => $data['name'], 'phone' => $data['phone']]);

        if (!$user->profile) {
            $user->profile()->create($data);
        } else {
            $user->profile->update($data);
        }

        return redirect()->back()->with('success', 'Profile updated!');
    }

    public function addSkill()
    {
        $data = request()->validate([
            'skill_name' => 'required|string',
            'proficiency_level' => 'required|in:beginner,intermediate,expert',
            'years_of_experience' => 'nullable|integer|min:0',
        ]);

        Auth::user()->skills()->create($data);

        return redirect()->back()->with('success', 'Skill added!');
    }

    public function deleteSkill(MemberSkill $skill)
    {
        $this->authorize('delete', $skill);
        $skill->delete();

        return redirect()->back()->with('success', 'Skill removed!');
    }

    public function approve(User $member)
    {
        $this->authorize('update', $member);

        $member->update(['is_active' => true]);

        return redirect()->back()->with('success', 'Member approved!');
    }

    public function pending()
    {
        $members = User::where('role', 'member')
                       ->where('is_active', false)
                       ->paginate(20);

        return view('members.pending', compact('members'));
    }
}
