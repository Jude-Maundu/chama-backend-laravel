<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\MpesaTransaction;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ContributionController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    public function index()
    {
        $contributions = Contribution::with('user')->latest()->paginate(request('per_page', 20));
        return response()->json($contributions);
    }

    public function create()
    {
        $members = User::role('member')->where('is_active', true)->get();
        return response()->json([
            'success' => true,
            'data' => $members
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mpesa,cash,bank_transfer',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        
        try {
            $penalty = $this->calculatePenalty($request->user_id, $request->payment_date);
            $totalAmount = $request->amount + $penalty;
            
            $contribution = Contribution::create([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'penalty' => $penalty,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'due_date' => $this->getDueDate(),
                'status' => $request->payment_method === 'cash' ? 'completed' : 'pending',
                'receipt_number' => $this->generateReceiptNumber(),
                'notes' => $request->notes,
                'recorded_by' => Auth::id(),
            ]);

            if ($request->payment_method === 'cash') {
                // Create transaction record
                Transaction::create([
                    'user_id' => $request->user_id,
                    'type' => 'contribution',
                    'direction' => 'credit',
                    'amount' => $totalAmount,
                    'balance_before' => $this->getUserBalance($request->user_id),
                    'balance_after' => $this->getUserBalance($request->user_id) + $totalAmount,
                    'description' => 'Cash contribution',
                    'transaction_date' => $request->payment_date,
                    'created_by' => Auth::id(),
                ]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'record_contribution',
                'table_name' => 'contributions',
                'record_id' => $contribution->id,
                'new_values' => json_encode($request->all()),
            ]);

            DB::commit();
            
            return redirect()->route('contributions.index')->with('success', 'Contribution recorded successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to record contribution: ' . $e->getMessage());
        }
    }

    public function mpesaPay(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'phone' => 'required|regex:/^254[0-9]{9}$/',
        ]);

        $user = User::find($request->user_id);
        $phone = $request->phone ?? $user->phone;
        
        $response = $this->mpesaService->stkPush(
            $phone,
            $request->amount,
            'CONTRIBUTION_' . time(),
            'Chama Contribution Payment'
        );

        if ($response && isset($response['CheckoutRequestID'])) {
            MpesaTransaction::create([
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'checkout_request_id' => $response['CheckoutRequestID'],
                'amount' => $request->amount,
                'phone_number' => $phone,
                'transaction_type' => 'stkpush',
                'status' => 'pending',
                'user_id' => $request->user_id,
                'reference_type' => 'contribution',
            ]);

            return response()->json(['success' => true, 'checkout_id' => $response['CheckoutRequestID']]);
        }

        return response()->json(['success' => false, 'message' => 'M-Pesa request failed'], 500);
    }

    public function receipt($id)
    {
        $contribution = Contribution::with('user')->findOrFail($id);
        
        $pdf = Pdf::loadView('contributions.receipt-pdf', compact('contribution'));
        return $pdf->download('receipt-' . $contribution->receipt_number . '.pdf');
    }

    public function history($memberId = null)
    {
        $userId = $memberId ?? Auth::id();
        $contributions = Contribution::where('user_id', $userId)->latest()->paginate(request('per_page', 20));
        
        return response()->json($contributions);
    }

    public function report()
    {
        $startDate = request('start_date', now()->startOfMonth());
        $endDate = request('end_date', now()->endOfMonth());
        
        $contributions = Contribution::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('user')
            ->get();
        
        $summary = [
            'total' => $contributions->sum('total_amount'),
            'by_method' => $contributions->groupBy('payment_method')->map->sum('total_amount'),
            'by_member' => $contributions->groupBy('user.name')->map->sum('total_amount'),
        ];
        
        return response()->json([
            'contributions' => $contributions,
            'summary' => $summary,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function pending()
    {
        $pendingContributions = Contribution::where('status', 'pending')->with('user')->paginate(request('per_page', 20));
        return response()->json($pendingContributions);
    }

    private function calculatePenalty($userId, $paymentDate)
    {
        $dueDate = $this->getDueDate();
        
        if ($paymentDate > $dueDate) {
            $daysLate = $dueDate->diffInDays($paymentDate);
            $penaltyRate = \App\Models\Setting::get('late_penalty_percentage', 5);
            $contributionAmount = \App\Models\Setting::get('monthly_contribution', 5000);
            
            return ($contributionAmount * $penaltyRate / 100) * ceil($daysLate / 30);
        }
        
        return 0;
    }

    private function getDueDate()
    {
        return now()->startOfMonth()->addDays(5);
    }

    private function generateReceiptNumber()
    {
        return 'RCP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function getUserBalance($userId)
    {
        return Transaction::where('user_id', $userId)->sum(DB::raw('CASE WHEN direction = "credit" THEN amount ELSE -amount END'));
    }
}