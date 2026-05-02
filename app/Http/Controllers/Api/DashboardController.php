<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Meeting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->hasRole('super-admin')) {
            return app(AdminController::class)->dashboardStats();
        } elseif ($user->hasRole('chama-admin') || $user->hasRole('admin')) {
            return $this->adminDashboard();
        } elseif ($user->hasRole('treasurer')) {
            return $this->treasurerDashboard();
        } elseif ($user->hasRole('secretary')) {
            return $this->secretaryDashboard();
        } else {
            return $this->memberDashboard();
        }
    }

    private function getCurrentChamaId()
    {
        $user = Auth::user();
        if ($user->current_chama_id) {
            return $user->current_chama_id;
        }

        $membership = \App\Models\ChamaMember::where('user_id', $user->id)->first();
        if ($membership) {
            $user->update(['current_chama_id' => $membership->chama_id]);
            return $membership->chama_id;
        }

        return null;
    }

    public function adminDashboard()
    {
        try {
            $chamaId = $this->getCurrentChamaId();
            
            if (!$chamaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active chama found. Please join or create a chama.',
                    'stats' => null
                ], 404);
            }
            
            $stats = [
                'total_members' => User::where('current_chama_id', $chamaId)->count(),
                'active_members' => User::where('current_chama_id', $chamaId)->where('is_active', true)->count(),
                'total_contributions' => Contribution::where('chama_id', $chamaId)->where('status', 'completed')->sum('total_amount') ?? 0,
                'monthly_contributions' => Contribution::where('chama_id', $chamaId)->where('payment_date', '>=', now()->startOfMonth())->sum('total_amount') ?? 0,
                'total_loans_disbursed' => Loan::where('chama_id', $chamaId)->where('status', 'disbursed')->sum('amount') ?? 0,
                'active_loans' => Loan::where('chama_id', $chamaId)->whereIn('status', ['approved', 'disbursed'])->sum('balance') ?? 0,
                'total_meetings' => Meeting::where('chama_id', $chamaId)->count(),
                'upcoming_meetings' => Meeting::where('chama_id', $chamaId)->where('meeting_date', '>', now())->count(),
                'recent_contributions' => Contribution::where('chama_id', $chamaId)->with('user')->latest()->take(10)->get(),
                'recent_loans' => Loan::where('chama_id', $chamaId)->with('user')->latest()->take(10)->get(),
                'pending_approvals' => User::where('current_chama_id', $chamaId)->where('is_active', false)->count() + Loan::where('chama_id', $chamaId)->where('status', 'pending')->count(),
            ];

            // Chart data
            $chartData = [
                'monthly_contributions' => Contribution::where('chama_id', $chamaId)->select(
                    DB::raw('MONTH(payment_date) as month'),
                    DB::raw('SUM(total_amount) as total')
                )->whereYear('payment_date', now()->year)
                ->groupBy('month')
                ->get(),
                
                'loan_distribution' => Loan::where('chama_id', $chamaId)->select('loan_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('loan_type')
                    ->get(),
            ];

            return response()->json(['stats' => $stats, 'chartData' => $chartData]);
        } catch (\Exception $e) {
            \Log::error('DashboardController.adminDashboard error: ' . $e->getMessage());
            return response()->json(['stats' => [], 'chartData' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function treasurerDashboard()
    {
        try {
            $chamaId = Auth::user()->current_chama_id;
            $stats = [
                'today_collections' => Contribution::where('chama_id', $chamaId)->whereDate('payment_date', today())->sum('total_amount') ?? 0,
                'week_collections' => Contribution::where('chama_id', $chamaId)->whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount') ?? 0,
                'pending_contributions' => Contribution::where('chama_id', $chamaId)->where('status', 'pending')->count(),
                'overdue_loans' => Loan::where('chama_id', $chamaId)->where('next_payment_date', '<', now())->where('balance', '>', 0)->count(),
                'mpesa_balance' => $this->getMpesaBalance($chamaId),
                'cash_balance' => (Transaction::where('chama_id', $chamaId)->where('type', 'contribution')->where('direction', 'credit')->sum('amount') ?? 0) - 
                                  (Transaction::where('chama_id', $chamaId)->where('type', 'expense')->where('direction', 'debit')->sum('amount') ?? 0),
            ];

            $recentTransactions = Transaction::where('chama_id', $chamaId)->with('user')->latest()->take(20)->get();
            
            return response()->json(['stats' => $stats, 'recentTransactions' => $recentTransactions]);
        } catch (\Exception $e) {
            \Log::error('DashboardController.treasurerDashboard error: ' . $e->getMessage());
            return response()->json(['stats' => [], 'recentTransactions' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function secretaryDashboard()
    {
        try {
            $chamaId = Auth::user()->current_chama_id;
            $stats = [
                'upcoming_meetings' => Meeting::where('chama_id', $chamaId)->where('meeting_date', '>', now())->count(),
                'past_meetings' => Meeting::where('chama_id', $chamaId)->where('meeting_date', '<=', now())->count(),
                'pending_minutes' => Meeting::where('chama_id', $chamaId)->where('meeting_date', '<=', now())->whereNull('minutes_path')->count(),
                'attendance_rate' => $this->getGlobalAttendanceRate($chamaId),
            ];

            $upcomingMeetings = Meeting::where('chama_id', $chamaId)->where('meeting_date', '>', now())->orderBy('meeting_date', 'asc')->take(5)->get();
            $recentMinutes = Meeting::where('chama_id', $chamaId)->whereNotNull('minutes_path')->latest()->take(5)->get();

            return response()->json([
                'stats' => $stats, 
                'upcomingMeetings' => $upcomingMeetings,
                'recentMinutes' => $recentMinutes
            ]);
        } catch (\Exception $e) {
            \Log::error('DashboardController.secretaryDashboard error: ' . $e->getMessage());
            return response()->json(['stats' => [], 'error' => $e->getMessage()], 500);
        }
    }

    private function getGlobalAttendanceRate($chamaId)
    {
        $totalMembers = User::where('current_chama_id', $chamaId)->count();
        $totalMeetings = Meeting::where('chama_id', $chamaId)->where('meeting_date', '<=', now())->count();
        
        if ($totalMembers == 0 || $totalMeetings == 0) return 0;
        
        $totalPossibleAttendances = $totalMembers * $totalMeetings;
        $actualAttendances = \App\Models\Attendance::whereHas('meeting', function($query) use ($chamaId) {
            $query->where('chama_id', $chamaId);
        })->where('status', 'present')->count();
        
        return round(($actualAttendances / $totalPossibleAttendances) * 100);
    }

    public function memberDashboard()
    {
        try {
            $user = Auth::user();

            $stats = [
                'total_contributions' => (float) ($user->contributions()->where('status', 'completed')->sum('total_amount') ?? 0),
                'outstanding_loans' => (float) ($user->loans()->whereNotIn('status', ['completed', 'rejected'])->sum('balance') ?? 0),
                'next_payment_due' => Contribution::where('user_id', $user->id)->where('status', 'pending')->orderBy('payment_date', 'asc')->first()?->payment_date?->format('M j, Y') ?? null,
                'attendance_rate' => $this->calculateAttendanceRate($user->id),
                'dividends_earned' => (float) ($user->dividends()->sum('amount') ?? 0),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('DashboardController.memberDashboard error: ' . $e->getMessage());
            return response()->json([
                'total_contributions' => 0,
                'outstanding_loans' => 0,
                'next_payment_due' => null,
                'attendance_rate' => 0,
                'dividends_earned' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateAttendanceRate($userId)
    {
        try {
            $totalMeetings = Meeting::where('meeting_date', '<=', now())->count();
            if ($totalMeetings === 0) return 0;

            $presentMeetings = \App\Models\Attendance::where('user_id', $userId)
                ->where('status', 'present')
                ->whereHas('meeting', function($query) {
                    $query->where('meeting_date', '<=', now());
                })
                ->count();

            return round(($presentMeetings / $totalMeetings) * 100);
        } catch (\Exception $e) {
            \Log::error('calculateAttendanceRate error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getMpesaBalance($chamaId = null)
    {
        // Call M-Pesa API to get balance for specific chama
        return 50000; // Placeholder
    }

    public function stats()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            
            if ($user->hasRole('super-admin')) {
                return app(AdminController::class)->dashboardStats();
            } elseif ($user->hasRole('chama-admin') || $user->hasRole('admin')) {
                return $this->adminDashboard();
            } elseif ($user->hasRole('treasurer')) {
                return $this->treasurerDashboard();
            } elseif ($user->hasRole('secretary')) {
                return $this->secretaryDashboard();
            } else {
                return $this->memberDashboard();
            }
        } catch (\Exception $e) {
            \Log::error('DashboardController.stats error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch stats', 'message' => $e->getMessage()], 500);
        }
    }

    public function chartData()
    {
        $user = Auth::user();
        
        $data = [
            'monthly_contributions' => Contribution::select(
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(total_amount) as total')
            )->whereYear('payment_date', now()->year)
            ->groupBy('month')
            ->get(),
            
            'loan_distribution' => Loan::select('loan_type', DB::raw('COUNT(*) as count'))
                ->groupBy('loan_type')
                ->get(),
        ];
        
        return response()->json($data);
    }
}