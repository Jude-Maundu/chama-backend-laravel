<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\Guarantor;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    public function index()
    {
        $loans = Loan::where('user_id', Auth::id())->with('user')->latest()->paginate(20);
        return response()->json($loans);
    }

    public function apply()
    {
        $maxLoanAmount = $this->calculateMaxLoanAmount(Auth::id());
        return response()->json([
            'success' => true,
            'max_loan_amount' => $maxLoanAmount
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'loan_type' => 'required|in:emergency,development,education,welfare',
            'amount' => 'required|numeric|min:1000',
            'duration_months' => 'required|integer|min:1|max:36',
            'purpose' => 'required|string',
        ]);

        $interestRate = $this->getInterestRate($request->loan_type);
        $monthlyPayment = $this->calculateMonthlyPayment($request->amount, $interestRate, $request->duration_months);
        $totalPayable = $monthlyPayment * $request->duration_months;

        $loan = Loan::create([
            'user_id' => Auth::id(),
            'chama_id' => Auth::user()->current_chama_id,
            'loan_type' => $request->loan_type,
            'amount' => $request->amount,
            'interest_rate' => $interestRate,
            'duration_months' => $request->duration_months,
            'monthly_payment' => $monthlyPayment,
            'total_payable' => $totalPayable,
            'balance' => $totalPayable,
            'purpose' => $request->purpose,
            'status' => 'pending',
            'application_date' => now(),
        ]);

        // Save guarantors if provided
        if ($request->guarantors) {
            foreach ($request->guarantors as $guarantorId) {
                Guarantor::create([
                    'loan_id' => $loan->id,
                    'guarantor_id' => $guarantorId,
                    'member_id' => Auth::id(),
                    'amount_guaranteed' => $request->amount,
                    'status' => 'pending',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Loan application submitted',
            'data' => $loan->load('guarantors')
        ], 201);
    }

    public function show($id)
    {
        $loan = Loan::with(['user', 'repayments', 'guarantors'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $loan
        ]);
    }

    public function approve($id)
    {
        $loan = Loan::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            $loan->update([
                'status' => 'approved',
                'approval_date' => now(),
                'approved_by' => Auth::id(),
            ]);

            // Generate repayment schedule
            $this->generateRepaymentSchedule($loan);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'approve_loan',
                'table_name' => 'loans',
                'record_id' => $loan->id,
                'new_values' => json_encode(['status' => 'approved']),
            ]);

            DB::commit();
            
            // Send notification
            // $this->sendNotification($loan->user_id, 'Loan Approved', 'Your loan has been approved');
            
            return redirect()->route('loans.show', $loan->id)->with('success', 'Loan approved successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to approve loan');
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);
        
        $loan = Loan::findOrFail($id);
        $loan->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);
        
        return redirect()->back()->with('success', 'Loan rejected');
    }

    public function disbursement($id)
    {
        $loan = Loan::findOrFail($id);
        
        // Send money via M-Pesa B2C
        $response = $this->mpesaService->b2c(
            $loan->user->phone,
            $loan->amount,
            'Loan Disbursement'
        );

        if ($response && isset($response['ConversationID'])) {
            $loan->update([
                'status' => 'disbursed',
                'disbursement_date' => now(),
                'mpesa_transaction_id' => $response['ConversationID'],
            ]);
            
            Transaction::create([
                'user_id' => $loan->user_id,
                'type' => 'loan_disbursement',
                'direction' => 'debit',
                'amount' => $loan->amount,
                'description' => 'Loan disbursement',
                'transaction_date' => now(),
                'created_by' => Auth::id(),
            ]);
            
            return redirect()->route('loans.show', $loan->id)->with('success', 'Loan disbursed successfully');
        }
        
        return back()->with('error', 'Failed to disburse loan');
    }

    public function repayForm($id)
    {
        $loan = Loan::findOrFail($id);
        $nextInstallment = $loan->repayments()->where('status', 'pending')->orderBy('installment_number')->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan,
                'nextInstallment' => $nextInstallment
            ]
        ]);
    }

    public function repay(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mpesa,cash,bank',
        ]);

        $loan = Loan::findOrFail($id);
        $nextInstallment = $loan->repayments()->where('status', 'pending')->orderBy('installment_number')->first();
        
        DB::beginTransaction();
        
        try {
            $amountPaid = $request->amount;
            $principalPaid = 0;
            $interestPaid = 0;
            $penaltyPaid = 0;
            
            // Calculate distribution (principal first, then interest)
            if ($amountPaid >= $nextInstallment->due_amount) {
                $principalPaid = $nextInstallment->due_amount - $nextInstallment->interest_paid;
                $interestPaid = $nextInstallment->interest_paid;
                $nextInstallment->status = 'paid';
                $nextInstallment->paid_date = now();
                $nextInstallment->paid_amount = $nextInstallment->due_amount;
                $nextInstallment->principal_paid = $principalPaid;
                $nextInstallment->interest_paid = $interestPaid;
                $nextInstallment->save();
            } else {
                // Partial payment - goes to interest first
                $interestPaid = min($amountPaid, $nextInstallment->interest_paid - $nextInstallment->interest_paid);
                $principalPaid = $amountPaid - $interestPaid;
                $nextInstallment->paid_amount += $amountPaid;
                $nextInstallment->principal_paid += $principalPaid;
                $nextInstallment->interest_paid += $interestPaid;
                $nextInstallment->status = 'partial';
                $nextInstallment->save();
            }
            
            // Update loan balance
            $newBalance = $loan->balance - $amountPaid;
            $loan->balance = max(0, $newBalance);
            
            if ($loan->balance == 0) {
                $loan->status = 'completed';
                $loan->completion_date = now();
            }
            
            $loan->save();
            
            // Record transaction
            Transaction::create([
                'user_id' => $loan->user_id,
                'type' => 'loan_repayment',
                'direction' => 'credit',
                'amount' => $amountPaid,
                'description' => "Loan repayment for loan #{$loan->id}",
                'transaction_date' => now(),
                'created_by' => Auth::id(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Repayment recorded successfully',
                'data' => $loan->load('repayments')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record repayment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function schedule($id)
    {
        $loan = Loan::with('repayments')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $loan
        ]);
    }

    public function defaulters()
    {
        $defaulters = Loan::where('next_payment_date', '<', now())
            ->where('balance', '>', 0)
            ->whereNotIn('status', ['completed', 'rejected'])
            ->with('user')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $defaulters
        ]);
    }

    public function pending()
    {
        $pendingLoans = Loan::where('status', 'pending')->with('user')->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $pendingLoans
        ]);
    }

    private function generateRepaymentSchedule($loan)
    {
        $monthlyInterest = $loan->interest_rate / 100 / 12;
        $balance = $loan->amount;
        
        for ($i = 1; $i <= $loan->duration_months; $i++) {
            $interestPayment = $balance * $monthlyInterest;
            $principalPayment = $loan->monthly_payment - $interestPayment;
            $balance -= $principalPayment;
            
            LoanRepayment::create([
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'due_amount' => $loan->monthly_payment,
                'principal_paid' => 0,
                'interest_paid' => 0,
                'balance_after' => max(0, $balance),
                'due_date' => now()->addMonths($i),
                'status' => 'pending',
            ]);
        }
    }

    private function calculateMaxLoanAmount($userId)
    {
        $totalContributions = Contribution::where('user_id', $userId)->where('status', 'completed')->sum('total_amount');
        $maxRatio = \App\Models\Setting::get('max_loan_ratio', 3);
        
        return $totalContributions * $maxRatio;
    }

    private function getInterestRate($loanType)
    {
        $rates = [
            'emergency' => 12,
            'development' => 10,
            'education' => 8,
            'welfare' => 5,
        ];
        
        return $rates[$loanType] ?? 10;
    }

    private function calculateMonthlyPayment($amount, $interestRate, $months)
    {
        $monthlyRate = $interestRate / 100 / 12;
        if ($monthlyRate == 0) return $amount / $months;
        
        return $amount * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
    }
}