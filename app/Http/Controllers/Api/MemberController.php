<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Profile;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function index()
    {
        $members = User::role('member')->with('profile')->paginate(15);
        return response()->json([
            'success' => true,
            'data' => $members
        ]);
    }

    public function create()
    {
        return response()->json(['message' => 'Use the registration endpoint to create members']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'national_id' => 'required|unique:profiles',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        Profile::create([
            'user_id' => $user->id,
            'national_id' => $request->national_id,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'occupation' => $request->occupation,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'join_date' => now(),
        ]);

        $user->assignRole('member');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_member',
            'table_name' => 'users',
            'record_id' => $user->id,
            'new_values' => json_encode($request->all()),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member created successfully',
            'data' => $user->load('profile')
        ], 201);
    }

    public function show($id)
    {
        $member = User::with(['profile', 'contributions', 'loans'])->findOrFail($id);
        
        $stats = [
            'total_contributions' => $member->contributions()->where('status', 'completed')->sum('total_amount'),
            'total_loans' => $member->loans()->sum('amount'),
            'outstanding_balance' => $member->loans()->whereNotIn('status', ['completed', 'rejected'])->sum('balance'),
            'attendance_rate' => $this->calculateAttendanceRate($member->id),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $member,
            'stats' => $stats
        ]);
    }

    public function edit($id)
    {
        $member = User::with('profile')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $member
        ]);
    }

    public function update(Request $request, $id)
    {
        $member = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|unique:users,phone,' . $id,
        ]);

        $member->update($request->only(['name', 'email', 'phone']));
        
        if ($member->profile) {
            $member->profile->update($request->only([
                'gender', 'occupation', 'address', 'city', 
                'emergency_contact_name', 'emergency_contact_phone',
                'bank_name', 'bank_account_number', 'mpesa_number'
            ]));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_member',
            'table_name' => 'users',
            'record_id' => $member->id,
            'new_values' => json_encode($request->all()),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member updated successfully',
            'data' => $member->load('profile')
        ]);
    }

    public function approve($id)
    {
        $member = User::findOrFail($id);
        $member->update(['is_active' => true]);
        
        return response()->json([
            'success' => true,
            'message' => 'Member approved successfully'
        ]);
    }

    public function pending()
    {
        $pendingMembers = User::role('member')->where('is_active', false)->paginate(15);
        return response()->json([
            'success' => true,
            'data' => $pendingMembers
        ]);
    }

    public function statement($id)
    {
        $member = User::findOrFail($id);
        
        $contributions = $member->contributions()->orderBy('payment_date')->get();
        $loans = $member->loans()->with('repayments')->get();
        $transactions = $member->transactions()->orderBy('transaction_date')->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'contributions' => $contributions,
                'loans' => $loans,
                'transactions' => $transactions
            ]
        ]);
    }

    public function activate($id)
    {
        $member = User::findOrFail($id);
        $member->update(['is_active' => true]);
        
        return response()->json(['success' => true]);
    }

    public function deactivate($id)
    {
        $member = User::findOrFail($id);
        $member->update(['is_active' => false]);
        
        return response()->json(['success' => true]);
    }

    private function calculateAttendanceRate($userId)
    {
        $totalMeetings = \App\Models\Attendance::where('user_id', $userId)->count();
        $presentMeetings = \App\Models\Attendance::where('user_id', $userId)->where('status', 'present')->count();
        
        return $totalMeetings > 0 ? ($presentMeetings / $totalMeetings) * 100 : 0;
    }
}