<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function financialReport(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $contributions = Contribution::where('chama_id', $user->chama_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $loanRepayments = Loan::where('chama_id', $user->chama_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount');

        $totalIncome = $contributions + $loanRepayments;

        $expenses = Transaction::where('chama_id', $user->chama_id)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return view('reports.financial', [
            'totalIncome' => $totalIncome,
            'totalExpenses' => $expenses,
            'netSurplus' => $totalIncome - $expenses,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function memberReport(Request $request)
    {
        $user = Auth::user();

        $members = User::where('chama_id', $user->chama_id)
            ->withCount('contributions')
            ->withCount('loans')
            ->get();

        return view('reports.members', compact('members'));
    }

    public function loanReport(Request $request)
    {
        $user = Auth::user();

        $loans = Loan::where('chama_id', $user->chama_id)
            ->with('member')
            ->get();

        $totalLoansIssued = $loans->sum('amount');
        $totalRepaid = $loans->where('status', 'completed')->sum('amount');
        $outstandingLoans = $loans->where('status', '!=', 'completed')->sum('amount');

        return view('reports.loans', [
            'loans' => $loans,
            'totalLoansIssued' => $totalLoansIssued,
            'totalRepaid' => $totalRepaid,
            'outstandingLoans' => $outstandingLoans
        ]);
    }

    public function contributionReport(Request $request)
    {
        $user = Auth::user();

        $contributions = Contribution::where('chama_id', $user->chama_id)
            ->with('member')
            ->orderBy('date', 'desc')
            ->paginate(20);

        $totalContributions = Contribution::where('chama_id', $user->chama_id)->sum('amount');
        $averageContribution = Contribution::where('chama_id', $user->chama_id)->avg('amount');

        return view('reports.contributions', [
            'contributions' => $contributions,
            'totalContributions' => $totalContributions,
            'averageContribution' => $averageContribution
        ]);
    }

    public function exportPdf(Request $request)
    {
        // TODO: Implement PDF export using Laravel PDF package
        return back()->with('info', 'PDF export coming soon');
    }

    public function exportExcel(Request $request)
    {
        // TODO: Implement Excel export using Laravel Excel package
        return back()->with('info', 'Excel export coming soon');
    }
}
