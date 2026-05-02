<?php

namespace App\Http\Controllers;

use App\Models\Chama;
use App\Models\User;
use App\Models\ChamaMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    // Super Admin Dashboard
    public function dashboard()
    {
        $stats = [
            'total_chamas' => Chama::count(),
            'total_users' => User::count(),
            'total_members' => ChamaMember::where('status', 'active')->count(),
            'active_chamas' => Chama::where('status', 'active')->count(),
        ];

        $recentChamas = Chama::latest()->take(10)->get();
        $recentUsers = User::latest()->take(10)->get();

        return view('superadmin.dashboard', compact('stats', 'recentChamas', 'recentUsers'));
    }

    // List all Chamas
    public function chamas()
    {
        $chamas = Chama::with('creator')->paginate(20);
        return view('superadmin.chamas', compact('chamas'));
    }

    // Create a new Chama (as Super Admin)
    public function createChama(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:chamas',
            'admin_email' => 'required|email|exists:users,email',
        ]);

        $chama = Chama::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(5),
            'created_by' => Auth::id(),
            'status' => 'active',
        ]);

        // Assign the specified user as Chama Admin
        $user = User::where('email', $request->admin_email)->first();
        
        ChamaMember::create([
            'chama_id' => $chama->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Chama created successfully!');
    }

    // List all users
    public function users()
    {
        $users = User::with('chamas')->paginate(20);
        return view('superadmin.users', compact('users'));
    }

    // Make a user Super Admin
    public function makeSuperAdmin($userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['is_super_admin' => true]);
        
        return response()->json(['success' => true]);
    }

    // Remove Super Admin status
    public function removeSuperAdmin($userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['is_super_admin' => false]);
        
        return response()->json(['success' => true]);
    }
}
