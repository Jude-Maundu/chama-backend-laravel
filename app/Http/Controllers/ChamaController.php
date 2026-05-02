<?php

namespace App\Http\Controllers;

use App\Models\Chama;
use App\Models\ChamaMember;
use App\Models\ChamaInvitation;
use App\Models\User;
use App\Notifications\ChamaJoined;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Swal;

class ChamaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // List all Chamas the user belongs to
    public function index()
    {
        $user = Auth::user();
        
        $myChamas = $user->chamas()->wherePivot('status', 'active')->get();
        $pendingInvites = $user->chamaInvitations()->where('status', 'pending')->get();
        
        return view('chamas.index', compact('myChamas', 'pendingInvites'));
    }

    // Show create chama form
    public function create()
    {
        return view('chamas.create');
    }

    // Store new chama
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:chamas',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            $chama = Chama::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . Str::random(5),
                'description' => $request->description,
                'location' => $request->location,
                'email' => $request->email,
                'phone' => $request->phone,
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            // Add creator as admin
            ChamaMember::create([
                'chama_id' => $chama->id,
                'user_id' => Auth::id(),
                'role' => 'admin',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // Create default settings for this chama
            $this->createDefaultSettings($chama->id);

            DB::commit();
            
            return redirect()->route('chamas.show', $chama->slug)
                ->with('success', 'Chama created successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to create chama: ' . $e->getMessage());
        }
    }

    // Show single chama dashboard
    public function show($slug)
    {
        $chama = Chama::where('slug', $slug)->firstOrFail();
        
        // Check if user is member
        $membership = $chama->members()->where('user_id', Auth::id())->first();
        
        if (!$membership) {
            abort(403, 'You are not a member of this chama');
        }

        $stats = [
            'total_members' => $chama->activeMembers()->count(),
            'total_savings' => $chama->getTotalSavings(),
            'total_loans' => $chama->getTotalLoans(),
            'outstanding_loans' => $chama->getOutstandingLoans(),
            'recent_contributions' => $chama->contributions()->with('user')->latest()->take(5)->get(),
            'upcoming_meetings' => $chama->meetings()->where('meeting_date', '>', now())->take(3)->get(),
        ];
        
        $userRole = $membership->role;
        
        return view('chamas.show', compact('chama', 'stats', 'userRole'));
    }

    // Invite members to chama
    public function invite(Request $request, $slug)
    {
        $chama = Chama::where('slug', $slug)->firstOrFail();
        
        // Check if user is admin
        if (!$chama->isAdmin(Auth::id())) {
            return response()->json(['error' => 'Only admins can invite members'], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,treasurer,secretary,member',
        ]);

        // Check if user already exists
        $user = User::where('email', $request->email)->first();
        
        $invitation = ChamaInvitation::create([
            'chama_id' => $chama->id,
            'invited_by' => Auth::id(),
            'email' => $request->email,
            'token' => Str::random(64),
            'role' => $request->role,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Send invitation email
        // Mail::to($request->email)->send(new ChamaInvitationMail($invitation));
        
        return response()->json(['success' => true, 'message' => 'Invitation sent successfully']);
    }

    // Accept invitation
    public function acceptInvitation($token)
    {
        $invitation = ChamaInvitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        DB::beginTransaction();
        
        try {
            $password = Str::random(12);

            // Get or create user
            $user = User::firstOrCreate(
                ['email' => $invitation->email],
                [
                    'name' => explode('@', $invitation->email)[0],
                    'password' => bcrypt($password),
                ]
            );

            $welcomePassword = $user->wasRecentlyCreated ? $password : null;

            // Add to chama
            ChamaMember::create([
                'chama_id' => $invitation->chama_id,
                'user_id' => $user->id,
                'role' => $invitation->role,
                'status' => 'active',
                'joined_at' => now(),
                'invited_by' => $invitation->invited_by,
            ]);

            // Mark invitation as accepted
            $invitation->update(['status' => 'accepted']);

            // Notify user by email once they join
            $user->notify(new ChamaJoined($invitation->chama->name, $invitation->role, $welcomePassword));

            DB::commit();
            
            // Log the user in if not already
            Auth::login($user);
            
            return redirect()->route('chamas.show', $invitation->chama->slug)
                ->with('success', 'You have joined the Chama successfully! A confirmation email has been sent to your address.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('login')->with('error', 'Failed to accept invitation');
        }
    }

    // Update member role
    public function updateRole(Request $request, $slug, $memberId)
    {
        $chama = Chama::where('slug', $slug)->firstOrFail();
        
        if (!$chama->isAdmin(Auth::id())) {
            return response()->json(['error' => 'Only admins can change roles'], 403);
        }

        $request->validate([
            'role' => 'required|in:admin,treasurer,secretary,member',
        ]);

        $member = ChamaMember::where('chama_id', $chama->id)
            ->where('user_id', $memberId)
            ->firstOrFail();

        $member->update(['role' => $request->role]);

        return response()->json(['success' => true]);
    }

    // Remove member from chama
    public function removeMember($slug, $memberId)
    {
        $chama = Chama::where('slug', $slug)->firstOrFail();
        
        if (!$chama->isAdmin(Auth::id())) {
            return response()->json(['error' => 'Only admins can remove members'], 403);
        }

        // Don't remove the last admin
        $adminCount = $chama->admins()->count();
        $isAdmin = $chama->isAdmin($memberId);
        
        if ($isAdmin && $adminCount <= 1) {
            return response()->json(['error' => 'Cannot remove the only admin'], 400);
        }

        ChamaMember::where('chama_id', $chama->id)
            ->where('user_id', $memberId)
            ->delete();

        return response()->json(['success' => true]);
    }

    // Switch between chamas (for navbar)
    public function switchChama($slug)
    {
        $chama = Chama::where('slug', $slug)->firstOrFail();
        
        if (!$chama->isMember(Auth::id())) {
            abort(403);
        }

        session(['current_chama_id' => $chama->id, 'current_chama_slug' => $chama->slug]);
        
        return redirect()->route('chamas.show', $chama->slug);
    }

    private function createDefaultSettings($chamaId)
    {
        $defaultSettings = [
            ['key' => 'monthly_contribution', 'value' => '5000', 'type' => 'decimal', 'group_name' => 'contributions'],
            ['key' => 'late_penalty_percentage', 'value' => '5', 'type' => 'decimal', 'group_name' => 'contributions'],
            ['key' => 'loan_interest_rate', 'value' => '10', 'type' => 'decimal', 'group_name' => 'loans'],
            ['key' => 'max_loan_ratio', 'value' => '3', 'type' => 'decimal', 'group_name' => 'loans'],
            ['key' => 'currency', 'value' => 'KES', 'type' => 'string', 'group_name' => 'general'],
        ];
        
        foreach ($defaultSettings as $setting) {
            $setting['chama_id'] = $chamaId;
            \App\Models\Setting::create($setting);
        }
    }
}
