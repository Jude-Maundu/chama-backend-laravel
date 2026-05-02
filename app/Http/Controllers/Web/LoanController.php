<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            $loans = Loan::with('user')->paginate(50);
            $activeLoans = Loan::where('status', 'active')->count();
            $totalDisbursed = Loan::where('status', 'disbursed')->sum('amount');
        } else {
            $loans = $user->loans()->paginate(20);
            $activeLoans = $user->loans()->where('status', 'active')->count();
            $totalDisbursed = $user->loans()->where('status', 'disbursed')->sum('amount');
        }

        $defaultRisk = $this->getDefaultRiskData();

        return view('loans.index', compact('loans', 'activeLoans', 'totalDisbursed', 'defaultRisk'));
    }

    public function show(Loan $loan)
    {
        $this->authorize('view', $loan);
        
        $repayments = $loan->repayments()->orderBy('due_date', 'desc')->get();
        $repaymentStats = [
            'paid' => $repayments->where('status', 'paid')->count(),
            'pending' => $repayments->where('status', 'pending')->count(),
            'overdue' => $repayments->where('status', 'overdue')->count(),
        ];

        return view('loans.show', compact('loan', 'repayments', 'repaymentStats'));
    }

    public function apply()
    {
        $eligibilityScore = Auth::user()->loanEligibilityScores()->latest()->first();
        $maxLoanAmount = $this->calculateMaxLoanAmount(Auth::user(), $eligibilityScore);

        return view('loans.apply', compact('eligibilityScore', 'maxLoanAmount'));
    }

    public function store()
    {
        $data = request()->validate([
            'amount' => 'required|numeric|min:1000',
            'duration_months' => 'required|integer|min:1|max:60',
            'purpose' => 'required|string',
            'loan_type' => 'required|in:personal,business,emergency',
        ]);

        $user = Auth::user();
        $maxAmount = $this->calculateMaxLoanAmount($user);

        if ($data['amount'] > $maxAmount) {
            return back()->withErrors(['amount' => "Maximum loan amount is KES " . number_format($maxAmount)]);
        }

        $interestRate = $this->calculateInterestRate($user, $data['loan_type']);
        $monthlyPayment = $this->calculateMonthlyPayment($data['amount'], $interestRate, $data['duration_months']);
        $totalPayable = $monthlyPayment * $data['duration_months'];

        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'interest_rate' => $interestRate,
            'duration_months' => $data['duration_months'],
            'monthly_payment' => $monthlyPayment,
            'total_payable' => $totalPayable,
            'balance' => $totalPayable,
            'purpose' => $data['purpose'],
            'loan_type' => $data['loan_type'],
            'status' => 'pending',
            'application_date' => now(),
        ]);

        return redirect()->route('loans.show', $loan)->with('success', 'Loan application submitted!');
    }

    public function approve(Loan $loan)
    {
        $this->authorize('update', $loan);

        $loan->update([
            'status' => 'approved',
            'approval_date' => now(),
            'approved_by' => Auth::id(),
        ]);

        return back()->with('success', 'Loan approved!');
    }

    public function disburse(Loan $loan)
    {
        $this->authorize('update', $loan);

        if ($loan->status !== 'approved') {
            return back()->withErrors(['status' => 'Loan must be approved first']);
        }

        $loan->update([
            'status' => 'disbursed',
            'disbursement_date' => now(),
            'first_payment_date' => now()->addMonth(),
            'next_payment_date' => now()->addMonth(),
        ]);

        $this->createRepaymentSchedule($loan);

        return back()->with('success', 'Loan disbursed!');
    }

    public function repayForm(Loan $loan)
    {
        $this->authorize('view', $loan);
        
        $nextPayment = $loan->repayments()->where('status', '!=', 'paid')->first();
        
        return view('loans.repay', compact('loan', 'nextPayment'));
    }

    public function repay(Loan $loan)
    {
        $this->authorize('view', $loan);
        
        $data = request()->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:mpesa,cash,bank',
        ]);

        $nextPayment = $loan->repayments()->where('status', '!=', 'paid')->first();

        if (!$nextPayment) {
            return back()->withErrors(['status' => 'No pending payments for this loan']);
        }

        $nextPayment->update([
            'status' => 'paid',
            'payment_date' => now(),
            'amount_paid' => $data['amount'],
        ]);

        $loan->decrement('balance', $data['amount']);

        if ($loan->balance <= 0) {
            $loan->update(['status' => 'completed']);
        }

        return back()->with('success', 'Payment recorded!');
    }

    private function calculateMaxLoanAmount($user, $eligibilityScore = null)
    {
        $contributions = $user->contributions()->sum('total_amount');
        return $contributions * 3; // Default: 3x contributions
    }

    private function calculateInterestRate($user, $loanType)
    {
        $baseRate = 10; // Base interest rate

        if ($loanType === 'emergency') {
            $baseRate = 12;
        } elseif ($loanType === 'business') {
            $baseRate = 15;
        }

        return $baseRate;
    }

    private function calculateMonthlyPayment($amount, $rate, $months)
    {
        $monthlyRate = $rate / 100 / 12;
        if ($monthlyRate == 0) return $amount / $months;
        return ($amount * $monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
    }

    private function createRepaymentSchedule(Loan $loan)
    {
        $startDate = $loan->first_payment_date;
        
        for ($i = 0; $i < $loan->duration_months; $i++) {
            LoanRepayment::create([
                'loan_id' => $loan->id,
                'due_date' => $startDate->copy()->addMonths($i),
                'amount' => $loan->monthly_payment,
                'status' => 'pending',
            ]);
        }
    }

    private function getDefaultRiskData()
    {
        return DB::table('loans')
            ->selectRaw('status, COUNT(*) as count')
            ->whereIn('status', ['active', 'overdue'])
            ->groupBy('status')
            ->get();
    }
}
