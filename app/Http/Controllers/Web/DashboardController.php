<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Contribution;
use App\Models\Loan;
use Illuminate\Support\Facades\Auth;    

class DashboardController extends Controller
{
    /**
     * Display the home page or dashboard
     */
    public function index()
    {
        if (!Auth::check()) {
            return view('welcome');
        }

        // User is authenticated, redirect to role-specific dashboard
        return $this->redirectToRoleDashboard();
    }

    /**
     * Redirect to appropriate dashboard based on user role
     */
    private function redirectToRoleDashboard()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return redirect()->route('dashboard.admin');
        } elseif ($user->role === 'treasurer') {
            return redirect()->route('dashboard.treasurer');
        } elseif ($user->role === 'member') {
            return redirect()->route('dashboard.member');
        }

        // Default fallback
        return view('dashboard.index');
    }

    /**
     * Admin dashboard
     */
    public function admin()
    {
        $totalMembers = User::whereHas('roles', function ($query) {
            $query->where('name', 'member');
        })->count();

        $totalContributions = Contribution::sum('total_amount');

        $activeLoans = Loan::where('status', 'active')
            ->orWhere('status', 'disbursed')
            ->count();

        $totalAssets = Contribution::sum('total_amount') - Loan::where('status', 'active')->sum('balance');

        return view('dashboard.admin', [
            'totalMembers' => $totalMembers,
            'totalContributions' => $totalContributions ?? 0,
            'activeLoans' => $activeLoans,
            'totalAssets' => $totalAssets ?? 0,
        ]);
    }

    /**
     * Treasurer dashboard
     */
    public function treasurer()
    {
        $user = Auth::user();
        
        $totalCollected = Contribution::sum('total_amount');
        
        $pendingPayments = Contribution::where('status', 'pending')->count();
        
        $activeLoans = Loan::where('status', 'active')
            ->orWhere('status', 'disbursed')
            ->count();
        
        $balance = (Contribution::sum('total_amount') ?? 0) - (Loan::where('status', 'active')->sum('balance') ?? 0);

        return view('dashboard.treasurer', [
            'totalCollected' => $totalCollected ?? 0,
            'pendingPayments' => $pendingPayments,
            'activeLoans' => $activeLoans,
            'balance' => $balance,
        ]);
    }

    /**
     * Member dashboard
     */
    public function member()
    {
        $user = Auth::user();
        
        $myContribution = Contribution::where('user_id', $user->id)->sum('total_amount');
        
        $totalMembers = User::whereHas('roles', function ($query) {
            $query->where('name', 'member');
        })->count();
        
        $myShare = $totalMembers > 0 ? round(($myContribution / Contribution::sum('total_amount') ?? 1) * 100, 2) : 0;
        
        $myLoans = Loan::where('user_id', $user->id)->count();
        
        $dividends = 0; // TODO: Implement dividend calculation

        return view('dashboard.member', [
            'myContribution' => $myContribution ?? 0,
            'myShare' => $myShare,
            'myLoans' => $myLoans,
            'dividends' => $dividends,
        ]);
    }
}