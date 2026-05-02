<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FinancialGoal;
use Illuminate\Support\Facades\Auth;

class FinancialGoalController extends Controller
{
    public function index()
    {
        $user = Auth::user();


        $goals = FinancialGoal::where('user_id', $user->id)->orderBy('target_date')->paginate(20);
        $totalTarget = $goals->sum('target_amount');
        $totalSaved = $goals->sum('current_amount');
        $progressPercent = $totalTarget > 0 ? ($totalSaved / $totalTarget) * 100 : 0;

        return view('goals.index', compact('goals', 'totalTarget', 'totalSaved', 'progressPercent'));
    }

    public function create()
    {
        return view('goals.create');
    }

    public function store()
    {
        $data = request()->validate([
            'goal_name' => 'required|string',
            'goal_description' => 'nullable|string',
            'target_amount' => 'required|numeric|min:1',
            'target_date' => 'required|date|after:now',
            'category' => 'required|in:education,health,investment,emergency,other',
        ]);

        $data['user_id'] = Auth::id();
        $data['current_amount'] = 0;
        $data['status'] = 'active';

        FinancialGoal::create($data);

        return redirect()->route('goals.index')->with('success', 'Goal created!');
    }

    public function contribute(FinancialGoal $goal)
    {
        $data = request()->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $goal->increment('current_amount', $data['amount']);

        if ($goal->current_amount >= $goal->target_amount) {
            $goal->update(['status' => 'achieved']);
        }

        return redirect()->back()->with('success', 'Contribution added!');
    }

    public function delete(FinancialGoal $goal)
    {
        $this->authorize('delete', $goal);
        $goal->delete();

        return redirect()->route('goals.index')->with('success', 'Goal deleted!');
    }
}
