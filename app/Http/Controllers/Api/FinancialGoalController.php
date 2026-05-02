<?php

namespace App\Http\Controllers\Api;

use App\Models\FinancialGoal;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FinancialGoalController extends Controller
{
    /**
     * Get goals for a Chama
     */
    public function index(Chama $chama)
    {
        $this->authorize('view', $chama);

        $goals = $chama->financialGoals()
                       ->with('currency')
                       ->orderBy('priority', 'desc')
                       ->get();

        return response()->json($goals);
    }

    /**
     * Create new financial goal
     */
    public function store(Request $request, Chama $chama)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'target_amount' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'target_date' => 'required|date|after:today',
            'priority' => 'sometimes|in:0,1,2',
            'icon' => 'sometimes|string',
        ]);

        $goal = $chama->financialGoals()->create($validated);

        return response()->json($goal, 201);
    }

    /**
     * Get goal details
     */
    public function show(Chama $chama, FinancialGoal $goal)
    {
        $this->authorize('view', $chama);

        return response()->json($goal->load('currency'));
    }

    /**
     * Update goal
     */
    public function update(Request $request, Chama $chama, FinancialGoal $goal)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'target_amount' => 'sometimes|numeric|min:0',
            'target_date' => 'sometimes|date',
            'status' => 'sometimes|in:active,completed,cancelled,paused',
            'priority' => 'sometimes|in:0,1,2',
        ]);

        $goal->update($validated);

        return response()->json($goal);
    }

    /**
     * Add progress to goal
     */
    public function addProgress(Request $request, Chama $chama, FinancialGoal $goal)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $goal->current_amount += $validated['amount'];
        if ($goal->current_amount >= $goal->target_amount) {
            $goal->status = 'completed';
        }
        $goal->save();

        return response()->json($goal);
    }

    /**
     * Get goal progress details
     */
    public function progress(Chama $chama, FinancialGoal $goal)
    {
        $this->authorize('view', $chama);

        return response()->json([
            'goal' => $goal,
            'progress_percentage' => $goal->getProgressPercentage(),
            'remaining_amount' => $goal->getRemainingAmount(),
            'is_on_track' => $goal->isOnTrack(),
            'days_remaining' => now()->diffInDays($goal->target_date, false),
        ]);
    }
}
