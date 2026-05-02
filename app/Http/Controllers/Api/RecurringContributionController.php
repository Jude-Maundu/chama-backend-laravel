<?php

namespace App\Http\Controllers\Api;

use App\Models\RecurringContribution;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RecurringContributionController extends Controller
{
    /**
     * Get recurring contributions for a Chama
     */
    public function index(Chama $chama)
    {
        $this->authorize('view', $chama);

        return response()->json(
            $chama->recurringContributions()->with('user')->get()
        );
    }

    /**
     * Get member's recurring contributions
     */
    public function userContributions()
    {
        $contributions = auth()->user()
            ->recurringContributions()
            ->with(['chama', 'user'])
            ->get();

        return response()->json($contributions);
    }

    /**
     * Create recurring contribution
     */
    public function store(Request $request, Chama $chama)
    {
        $this->authorize('create-contribution', $chama);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:daily,weekly,bi_weekly,monthly,quarterly,yearly',
            'payment_method' => 'required|in:mpesa,airtel_money,tigo_pesa,bank_transfer',
            'start_date' => 'required|date|after:today',
        ]);

        $contribution = RecurringContribution::create([
            'chama_id' => $chama->id,
            'user_id' => auth()->id(),
            ...$validated,
            'next_due_date' => now()->parse($validated['start_date']),
        ]);

        return response()->json($contribution, 201);
    }

    /**
     * Update recurring contribution
     */
    public function update(Request $request, RecurringContribution $contribution)
    {
        $this->authorize('update', $contribution);

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'frequency' => 'sometimes|in:daily,weekly,bi_weekly,monthly,quarterly,yearly',
            'payment_method' => 'sometimes|in:mpesa,airtel_money,tigo_pesa,bank_transfer',
            'status' => 'sometimes|in:active,paused,cancelled',
        ]);

        $contribution->update($validated);

        return response()->json($contribution);
    }

    /**
     * Pause recurring contribution
     */
    public function pause(RecurringContribution $contribution)
    {
        $this->authorize('update', $contribution);

        $contribution->update(['status' => 'paused']);
        return response()->json(['message' => 'Contribution paused']);
    }

    /**
     * Resume recurring contribution
     */
    public function resume(RecurringContribution $contribution)
    {
        $this->authorize('update', $contribution);

        $contribution->update(['status' => 'active']);
        return response()->json(['message' => 'Contribution resumed']);
    }

    /**
     * Cancel recurring contribution
     */
    public function cancel(RecurringContribution $contribution)
    {
        $this->authorize('delete', $contribution);

        $contribution->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Contribution cancelled']);
    }
}
