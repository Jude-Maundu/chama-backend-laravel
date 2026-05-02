<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\User;
use App\Models\ChamaMember;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ChamaAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    private function getCurrentChamaId()
    {
        $user = Auth::user();
        if ($user->current_chama_id) {
            return $user->current_chama_id;
        }

        // Fallback to first chama where user is admin
        $membership = ChamaMember::where('user_id', $user->id)
            ->where('role', 'admin')
            ->first();
        
        if ($membership) {
            $user->update(['current_chama_id' => $membership->chama_id]);
            return $membership->chama_id;
        }

        return null;
    }

    public function getMyChamas()
    {
        $user = Auth::user();
        $chamas = $user->chamas()->get();
        
        return response()->json([
            'success' => true,
            'data' => $chamas,
            'current' => $user->currentChama
        ]);
    }

    public function getInviteCode()
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) {
            return response()->json(['error' => 'No active chama found'], 404);
        }

        $chama = Chama::find($chamaId);

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $chama->name,
                'join_code' => $chama->join_code,
                'slug' => $chama->slug,
            ]
        ]);
    }

    public function regenerateInviteCode()
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) {
            return response()->json(['error' => 'No active chama found'], 404);
        }

        $chama = Chama::find($chamaId);
        $chama->update(['join_code' => $this->generateUniqueJoinCode()]);

        return response()->json([
            'success' => true,
            'data' => [
                'join_code' => $chama->join_code
            ]
        ]);
    }

    private function generateUniqueJoinCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Chama::where('join_code', $code)->exists());

        return $code;
    }

    public function switchChama(Request $request, $slug)
    {
        $chama = Chama::where('slug', $slug)->firstOrFail();
        
        // Check if user is member
        if (!$chama->isMember(Auth::id())) {
            return response()->json(['error' => 'You are not a member of this chama'], 403);
        }

        Auth::user()->update(['current_chama_id' => $chama->id]);

        return response()->json([
            'success' => true,
            'message' => 'Switched to chama: ' . $chama->name,
            'data' => $chama
        ]);
    }

    public function dashboard()
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) return response()->json(['error' => 'No active chama found'], 404);

        $chama = Chama::findOrFail($chamaId);

        $stats = [
            'total_members' => $chama->members()->count(),
            'active_members' => $chama->members()->where('is_active', true)->count(),
            'total_contributions' => Contribution::where('chama_id', $chamaId)->sum('amount'),
            'monthly_contributions' => Contribution::where('chama_id', $chamaId)
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'active_loans_count' => Loan::where('chama_id', $chamaId)->whereIn('status', ['disbursed', 'repaying'])->count(),
            'default_rate' => 0, // Calculate if possible
            'available_balance' => Contribution::where('chama_id', $chamaId)->sum('amount') - Loan::where('chama_id', $chamaId)->whereIn('status', ['disbursed', 'repaying', 'completed'])->sum('amount'),
            'reserve_fund' => 0,
            'monthly_trend' => [5000, 15000, 12000, 25000, 30000, 45000, 40000, 55000, 60000, 70000, 65000, 80000], // Mock trend
            'disbursed_loans' => Loan::where('chama_id', $chamaId)->where('status', 'disbursed')->count(),
            'pending_loans_count' => Loan::where('chama_id', $chamaId)->where('status', 'pending')->count(),
            'repaid_loans' => Loan::where('chama_id', $chamaId)->where('status', 'completed')->count(),
            'defaulted_loans' => Loan::where('chama_id', $chamaId)->where('status', 'defaulted')->count(),
        ];

        $recentMembers = User::whereHas('chamas', function($query) use ($chamaId) {
            $query->where('chamas.id', $chamaId);
        })->latest()->limit(5)->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone,
                'created_at' => $user->created_at,
                'is_active' => $user->is_active
            ];
        });

        $recentContributions = Contribution::where('chama_id', $chamaId)
            ->with('user')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->id,
                    'member_name' => $c->user->name,
                    'amount' => $c->amount,
                    'created_at' => $c->created_at,
                    'status' => 'completed'
                ];
            });

        $pendingLoans = Loan::where('chama_id', $chamaId)
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->get()
            ->map(function($l) {
                return [
                    'id' => $l->id,
                    'applicant_name' => $l->user->name,
                    'amount' => $l->amount,
                    'purpose' => $l->purpose,
                    'created_at' => $l->created_at
                ];
            });

        return response()->json([
            'success' => true,
            'chama_name' => $chama->name,
            'stats' => $stats,
            'recent_members' => $recentMembers,
            'recent_contributions' => $recentContributions,
            'pending_loans' => $pendingLoans
        ]);
    }

    public function getMembers()
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) return response()->json(['error' => 'No active chama found'], 404);

        $members = User::whereHas('chamas', function($query) use ($chamaId) {
            $query->where('chamas.id', $chamaId);
        })->with(['profile', 'contributions' => function($q) use ($chamaId) {
            $q->where('chama_id', $chamaId);
        }])->get()->map(function($user) use ($chamaId) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone,
                'created_at' => $user->created_at,
                'is_active' => $user->is_active,
                'total_contributions' => $user->contributions->sum('amount'),
                'id_number' => $user->profile->id_number ?? 'N/A',
                'balance' => 0, // Placeholder
                'total_loans' => Loan::where('user_id', $user->id)->where('chama_id', $chamaId)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $members
        ]);
    }

    public function addMember(Request $request)
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) return response()->json(['error' => 'No active chama found'], 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'nullable|min:8',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password'] ?? 'password'),
                'is_active' => true,
            ]);

            $user->assignRole('member');

            ChamaMember::create([
                'chama_id' => $chamaId,
                'user_id' => $user->id,
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'data' => $user], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function removeMember($memberId)
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) return response()->json(['error' => 'No active chama found'], 404);

        ChamaMember::where('chama_id', $chamaId)
            ->where('user_id', $memberId)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function getLoans()
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) return response()->json(['error' => 'No active chama found'], 404);

        $loans = Loan::where('chama_id', $chamaId)->with('user')->get()->map(function($l) {
            return [
                'id' => $l->id,
                'borrower_name' => $l->user->name,
                'amount' => $l->amount,
                'duration_months' => $l->duration_months,
                'interest_rate' => $l->interest_rate,
                'status' => $l->status,
                'created_at' => $l->created_at,
                'purpose' => $l->purpose,
                'monthly_payment' => $l->repayment_amount / $l->duration_months,
                'amount_paid' => 0, // Placeholder
                'remaining_balance' => $l->repayment_amount,
                'approved_at' => $l->approved_at
            ];
        });

        $stats = [
            'total_loans' => Loan::where('chama_id', $chamaId)->count(),
            'total_amount' => Loan::where('chama_id', $chamaId)->sum('amount'),
            'pending_loans' => Loan::where('chama_id', $chamaId)->where('status', 'pending')->count(),
            'pending_amount' => Loan::where('chama_id', $chamaId)->where('status', 'pending')->sum('amount'),
            'active_loans' => Loan::where('chama_id', $chamaId)->whereIn('status', ['disbursed', 'repaying'])->count(),
            'outstanding_amount' => Loan::where('chama_id', $chamaId)->whereIn('status', ['disbursed', 'repaying'])->sum('amount'), // simplified
            'default_rate' => 0,
            'defaulted_loans' => Loan::where('chama_id', $chamaId)->where('status', 'defaulted')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $loans,
            'stats' => $stats
        ]);
    }

    public function approveLoan($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $loan->update([
            'status' => 'approved',
            'approved_at' => now()
        ]);
        return response()->json(['success' => true]);
    }

    public function rejectLoan($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $loan->update(['status' => 'rejected']);
        return response()->json(['success' => true]);
    }

    public function disburseLoan($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $loan->update(['status' => 'disbursed']);
        return response()->json(['success' => true]);
    }

    public function getFinances()
    {
        $chamaId = $this->getCurrentChamaId();
        if (!$chamaId) return response()->json(['error' => 'No active chama found'], 404);

        $totalContributions = Contribution::where('chama_id', $chamaId)->sum('amount');
        $totalLoans = Loan::where('chama_id', $chamaId)->where('status', 'disbursed')->sum('amount');

        $financials = [
            'total_contributions' => $totalContributions,
            'active_contributors' => ChamaMember::where('chama_id', $chamaId)->where('status', 'active')->count(),
            'total_loans_disbursed' => $totalLoans,
            'loan_count' => Loan::where('chama_id', $chamaId)->where('status', 'disbursed')->count(),
            'total_dividends' => 0,
            'avg_dividend_per_member' => 0,
            'reserve_fund' => $totalContributions * 0.1, // 10% reserve
            'financial_health' => 'Excellent',
            'monthly_revenue' => [10000, 20000, 15000, 25000, 35000, 30000],
            'monthly_expenses' => [2000, 4000, 3000, 5000, 7000, 6000],
            'income_sources' => [
                ['name' => 'Monthly Contributions', 'amount' => $totalContributions, 'percentage' => 85],
                ['name' => 'Loan Interest', 'amount' => $totalLoans * 0.1, 'percentage' => 10],
                ['name' => 'Fines & Penalties', 'amount' => 500, 'percentage' => 5],
            ],
            'expense_categories' => [
                ['name' => 'Administrative', 'amount' => 2000, 'percentage' => 40],
                ['name' => 'Bank Charges', 'amount' => 500, 'percentage' => 10],
                ['name' => 'Events', 'amount' => 2500, 'percentage' => 50],
            ]
        ];

        $transactions = Contribution::where('chama_id', $chamaId)
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->id,
                    'created_at' => $c->created_at,
                    'type' => 'contribution',
                    'member_name' => $c->user->name,
                    'description' => 'Monthly Contribution',
                    'amount' => $c->amount,
                    'balance' => $c->amount, // simplified
                ];
            });

        return response()->json([
            'success' => true,
            'financials' => $financials,
            'transactions' => $transactions
        ]);
    }
}
