<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContributionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            $contributions = Contribution::with('user')->paginate(50);
            $totalAmount = Contribution::sum('total_amount');
            $pendingCount = Contribution::where('status', 'pending')->count();
            $completedCount = Contribution::where('status', 'completed')->count();
        } else {
            $contributions = $user->contributions()->paginate(20);
            $totalAmount = $user->contributions()->sum('total_amount');
            $pendingCount = $user->contributions()->where('status', 'pending')->count();
            $completedCount = $user->contributions()->where('status', 'completed')->count();
        }

        $chartData = $this->getContributionChartData($user);

        return view('contributions.index', compact('contributions', 'totalAmount', 'pendingCount', 'completedCount', 'chartData'));
    }

    public function create()
    {
        $users = User::where('role', 'member')->get();
        return view('contributions.create', compact('users'));
    }

    public function store()
    {
        $data = request()->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:mpesa,cash,bank',
            'notes' => 'nullable|string',
        ]);

        $data['status'] = 'completed';
        $data['total_amount'] = $data['amount'];
        $data['payment_date'] = now();
        $data['recorded_by'] = Auth::id();

        Contribution::create($data);

        return redirect()->route('contributions.index')->with('success', 'Contribution recorded successfully!');
    }

    public function history($userId)
    {
        $user = User::findOrFail($userId);
        $contributions = $user->contributions()->orderBy('payment_date', 'desc')->paginate(20);
        $totalAmount = $user->contributions()->sum('total_amount');
        $monthlyAverage = $this->calculateMonthlyAverage($user);

        return view('contributions.history', compact('user', 'contributions', 'totalAmount', 'monthlyAverage'));
    }

    public function report()
    {
        $totalContributions = Contribution::sum('total_amount');
        $averageContribution = Contribution::avg('total_amount');
        $topContributors = User::withCount('contributions')
            ->orderByDesc('contributions_count')
            ->limit(10)
            ->get();

        $monthlyData = DB::table('contributions')
            ->selectRaw('MONTH(payment_date) as month, SUM(total_amount) as total')
            ->whereYear('payment_date', now()->year)
            ->groupBy('month')
            ->get();

        return view('contributions.report', compact('totalContributions', 'averageContribution', 'topContributors', 'monthlyData'));
    }

    private function getContributionChartData($user)
    {
        $data = DB::table('contributions')
            ->selectRaw('DATE(payment_date) as date, SUM(total_amount) as amount')
            ->when($user->role !== 'admin', function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->whereDate('payment_date', '>=', now()->subMonths(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data;
    }

    private function calculateMonthlyAverage($user)
    {
        $contributions = $user->contributions()->get();
        if ($contributions->isEmpty()) return 0;

        $months = $contributions->groupBy(function ($item) {
            return $item->payment_date->format('Y-m');
        })->count();

        return $months > 0 ? $contributions->sum('total_amount') / $months : 0;
    }
}
