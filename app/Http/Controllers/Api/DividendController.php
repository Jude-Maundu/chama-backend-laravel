<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Dividend;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DividendController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    public function index()
    {
        $dividends = Dividend::with(['calculator', 'approver'])->latest()->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $dividends
        ]);
    }

    public function calculate()
    {
        $totalShares = $this->calculateTotalShares();
        $totalProfit = $this->calculateTotalProfit();
        $perShareAmount = $totalShares > 0 ? $totalProfit / $totalShares : 0;
        
        $members = User::role('member')->where('is_active', true)->get();
        $memberShares = [];
        
        foreach ($members as $member) {
            $memberShares[] = [
                'user' => $member,
                'shares' => $this->calculateMemberShares($member->id),
                'dividend_amount' => $this->calculateMemberShares($member->id) * $perShareAmount,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'totalShares' => $totalShares,
                'totalProfit' => $totalProfit,
                'perShareAmount' => $perShareAmount,
                'memberShares' => $memberShares
            ]
        ]);
    }

    public function processCalculation(Request $request)
    {
        $request->validate([
            'period' => 'required|string',
            'total_amount' => 'required|numeric',
            'per_share_amount' => 'required|numeric',
        ]);

        DB::beginTransaction();
        
        try {
            $dividend = Dividend::create([
                'period' => $request->period,
                'total_amount' => $request->total_amount,
                'total_shares' => $request->total_shares,
                'per_share_amount' => $request->per_share_amount,
                'calculation_date' => now(),
                'status' => 'calculated',
                'calculated_by' => Auth::id(),
            ]);

            // Attach members with their dividend amounts
            foreach ($request->member_dividends as $memberData) {
                DB::table('dividend_user')->insert([
                    'dividend_id' => $dividend->id,
                    'user_id' => $memberData['user_id'],
                    'amount' => $memberData['amount'],
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'calculate_dividend',
                'table_name' => 'dividends',
                'record_id' => $dividend->id,
                'new_values' => json_encode($request->all()),
            ]);

            DB::commit();
            
            return redirect()->route('dividends.index')->with('success', 'Dividends calculated successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to calculate dividends: ' . $e->getMessage());
        }
    }

    public function distribute($id)
    {
        $dividend = Dividend::with(['members'])->findOrFail($id);
        
        if ($dividend->status !== 'calculated') {
            return back()->with('error', 'Dividends already distributed or not calculated');
        }

        DB::beginTransaction();
        
        try {
            foreach ($dividend->members as $member) {
                $pivot = $member->pivot;
                
                if ($pivot->status === 'pending') {
                    // Send via M-Pesa
                    $response = $this->mpesaService->b2c(
                        $member->phone,
                        $pivot->amount,
                        "Dividend payment for {$dividend->period}"
                    );

                    if ($response && isset($response['ConversationID'])) {
                        DB::table('dividend_user')
                            ->where('dividend_id', $dividend->id)
                            ->where('user_id', $member->id)
                            ->update([
                                'status' => 'distributed',
                                'mpesa_transaction_id' => $response['ConversationID'],
                                'distributed_at' => now(),
                            ]);

                        // Record transaction
                        Transaction::create([
                            'user_id' => $member->id,
                            'type' => 'dividend',
                            'direction' => 'debit',
                            'amount' => $pivot->amount,
                            'description' => "Dividend payment for {$dividend->period}",
                            'transaction_date' => now(),
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            $dividend->update([
                'status' => 'distributed',
                'distribution_date' => now(),
                'approved_by' => Auth::id(),
            ]);

            DB::commit();
            
            return redirect()->route('dividends.index')->with('success', 'Dividends distributed successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to distribute dividends: ' . $e->getMessage());
        }
    }

    public function myDividends()
    {
        $dividends = DB::table('dividend_user')
            ->where('user_id', Auth::id())
            ->join('dividends', 'dividends.id', '=', 'dividend_user.dividend_id')
            ->select('dividends.*', 'dividend_user.amount', 'dividend_user.status', 'dividend_user.distributed_at')
            ->orderBy('dividends.calculation_date', 'desc')
            ->get();
            
        return response()->json($dividends);
    }

    private function calculateTotalShares()
    {
        return User::role('member')->sum('total_contributions');
    }

    private function calculateTotalProfit()
    {
        $totalIncome = Transaction::where('direction', 'credit')->sum('amount');
        $totalExpenses = Transaction::where('direction', 'debit')
            ->whereNotIn('type', ['dividend'])
            ->sum('amount');
            
        return $totalIncome - $totalExpenses;
    }

    private function calculateMemberShares($userId)
    {
        return Contribution::where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('total_amount');
    }
}