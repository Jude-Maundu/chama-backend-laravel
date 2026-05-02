<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\Investment;
use App\Models\LoanRepayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function financial(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfYear();
        $endDate = $request->end_date ?? now();
        
        $income = [
            'contributions' => Contribution::whereBetween('payment_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('total_amount'),
            'loan_interest' => Loan::whereBetween('disbursement_date', [$startDate, $endDate])
                ->sum(DB::raw('(total_payable - amount)')),
            'penalties' => Contribution::whereBetween('payment_date', [$startDate, $endDate])
                ->sum('penalty'),
        ];
        
        $expenses = [
            'loan_disbursements' => Loan::whereBetween('disbursement_date', [$startDate, $endDate])
                ->sum('amount'),
            'dividends' => DB::table('dividend_user')->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'operational' => Transaction::where('type', 'expense')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount'),
        ];
        
        $totalIncome = array_sum($income);
        $totalExpenses = array_sum($expenses);
        $netProfit = $totalIncome - $totalExpenses;
        
        return response()->json([
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    public function exportFinancial(Request $request, $format)
    {
        $data = $this->financial($request)->getData(true);
        
        if ($format === 'pdf') {
            // Check if view exists, if not use a simple HTML string for demo
            if (view()->exists('reports.financial-pdf')) {
                $pdf = Pdf::loadView('reports.financial-pdf', $data);
            } else {
                $html = "<h1>Financial Report</h1><p>Period: {$data['period']['start_date']} to {$data['period']['end_date']}</p>";
                $html .= "<h3>Income</h3><ul>";
                foreach ($data['income'] as $k => $v) $html .= "<li>$k: $v</li>";
                $html .= "</ul><h3>Expenses</h3><ul>";
                foreach ($data['expenses'] as $k => $v) $html .= "<li>$k: $v</li>";
                $html .= "</ul><h3>Net Profit: {$data['net_profit']}</h3>";
                $pdf = Pdf::loadHTML($html);
            }
            return $pdf->download("financial-report.pdf");
        }
        
        if ($format === 'excel' || $format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="financial-report.csv"',
            ];
            
            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Financial Report', "{$data['period']['start_date']} to {$data['period']['end_date']}"]);
                fputcsv($file, []);
                fputcsv($file, ['Income Category', 'Amount']);
                foreach ($data['income'] as $category => $amount) {
                    fputcsv($file, [ucfirst(str_replace('_', ' ', $category)), $amount]);
                }
                fputcsv($file, ['Total Income', $data['total_income']]);
                fputcsv($file, []);
                fputcsv($file, ['Expense Category', 'Amount']);
                foreach ($data['expenses'] as $category => $amount) {
                    fputcsv($file, [ucfirst(str_replace('_', ' ', $category)), $amount]);
                }
                fputcsv($file, ['Total Expenses', $data['total_expenses']]);
                fputcsv($file, []);
                fputcsv($file, ['Net Profit', $data['net_profit']]);
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        }
        
        return response()->json(['error' => 'Invalid format'], 400);
    }

    public function export(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Generic export endpoint. Please use specific report export routes.'
        ]);
    }

    public function contributions(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now();
        
        $contributions = Contribution::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('user')
            ->get();
        
        $summary = [
            'total' => $contributions->sum('total_amount'),
            'by_method' => $contributions->groupBy('payment_method')->map->sum('total_amount'),
            'top_contributors' => $contributions->groupBy('user.name')->map->sum('total_amount')->sortDesc()->take(10),
            'collection_rate' => $this->calculateCollectionRate($startDate, $endDate),
        ];
        
        return response()->json([
            'contributions' => $contributions,
            'summary' => $summary,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    public function loans(Request $request)
    {
        $loans = Loan::with('user')->get();
        
        $portfolio = [
            'total_disbursed' => $loans->sum('amount'),
            'total_repaid' => LoanRepayment::sum('paid_amount'),
            'outstanding' => $loans->sum('balance'),
            'default_rate' => $this->calculateDefaultRate(),
            'by_type' => $loans->groupBy('loan_type')->map->sum('amount'),
        ];
        
        return response()->json([
            'loans' => $loans,
            'portfolio' => $portfolio
        ]);
    }

    public function memberStatement($memberId, Request $request)
    {
        $member = User::findOrFail($memberId);
        $startDate = $request->start_date ?? $member->join_date;
        $endDate = $request->end_date ?? now();
        
        $contributions = $member->contributions()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();
        
        $loans = $member->loans()
            ->whereBetween('disbursement_date', [$startDate, $endDate])
            ->get();
        
        $transactions = $member->transactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();
        
        $balance = $transactions->sum(DB::raw('CASE WHEN direction = "credit" THEN amount ELSE -amount END'));
        
        if ($request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.member-statement-pdf', compact('member', 'contributions', 'loans', 'transactions', 'balance', 'startDate', 'endDate'));
            return $pdf->download("statement-{$member->name}.pdf");
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'contributions' => $contributions,
                'loans' => $loans,
                'transactions' => $transactions,
                'balance' => $balance,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $asAtDate = $request->date ?? now();
        
        $assets = [
            'cash_balance' => $this->getCashBalance($asAtDate),
            'loan_portfolio' => Loan::where('created_at', '<=', $asAtDate)->sum('balance'),
            'investments' => Investment::sum('current_value'),
        ];
        
        $liabilities = [
            'member_savings' => $this->getTotalSavings($asAtDate),
            'pending_dividends' => DB::table('dividend_user')->where('status', 'pending')->sum('amount'),
        ];
        
        $equity = array_sum($assets) - array_sum($liabilities);
        
        return response()->json([
            'success' => true,
            'data' => [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'asAtDate' => $asAtDate
            ]
        ]);
    }

    public function audit(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn($q) => $q->where('action', $request->action))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(50);
        
        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function cashflow(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now();
        
        $inflows = Transaction::where('direction', 'credit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get();
        
        $outflows = Transaction::where('direction', 'debit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get();
        
        $netCashflow = $inflows->sum('total') - $outflows->sum('total');
        
        return response()->json([
            'success' => true,
            'data' => [
                'inflows' => $inflows,
                'outflows' => $outflows,
                'netCashflow' => $netCashflow,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]
        ]);
    }

    public function savingsProjection(Request $request)
    {
        $userId = $request->user_id ?? auth()->id();
        $months = $request->months ?? 12;
        $growthRate = $request->growth_rate ?? 0; // percentage increase in monthly contribution

        $currentSavings = Contribution::where('user_id', $userId)
                                     ->where('status', 'completed')
                                     ->sum('amount');
        
        $lastMonthSavings = Contribution::where('user_id', $userId)
                                        ->where('status', 'completed')
                                        ->whereMonth('payment_date', now()->subMonth()->month)
                                        ->sum('amount');

        if ($lastMonthSavings == 0) {
            $lastMonthSavings = Contribution::where('user_id', $userId)
                                            ->where('status', 'completed')
                                            ->avg('amount') ?? 0;
        }

        $projections = [];
        $runningTotal = $currentSavings;
        $monthlyContribution = $lastMonthSavings;

        for ($i = 1; $i <= $months; $i++) {
            $monthlyContribution *= (1 + ($growthRate / 100));
            $runningTotal += $monthlyContribution;
            $projections[] = [
                'month' => now()->addMonths($i)->format('Y-m'),
                'contribution' => round($monthlyContribution, 2),
                'projected_total' => round($runningTotal, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_savings' => $currentSavings,
                'monthly_base' => $lastMonthSavings,
                'projections' => $projections
            ]
        ]);
    }

    private function calculateCollectionRate($startDate, $endDate)
    {
        $expected = User::role('member')->count() * 5000 * $startDate->diffInMonths($endDate);
        $actual = Contribution::whereBetween('payment_date', [$startDate, $endDate])->sum('total_amount');
        
        return $expected > 0 ? ($actual / $expected) * 100 : 0;
    }

    private function calculateDefaultRate()
    {
        $totalLoans = Loan::count();
        $defaultedLoans = Loan::where('status', 'defaulted')->count();
        
        return $totalLoans > 0 ? ($defaultedLoans / $totalLoans) * 100 : 0;
    }

    private function getCashBalance($date)
    {
        return Transaction::where('transaction_date', '<=', $date)
            ->sum(DB::raw('CASE WHEN direction = "credit" THEN amount ELSE -amount END'));
    }

    private function getTotalSavings($date)
    {
        return Contribution::where('payment_date', '<=', $date)
            ->where('status', 'completed')
            ->sum('total_amount');
    }
}