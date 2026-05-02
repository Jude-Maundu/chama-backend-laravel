<?php

namespace App\Http\Controllers\Api;

use App\Models\EmergencyFund;
use App\Models\EmergencyWithdrawal;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EmergencyFundController extends Controller
{
    /**
     * Get Chama's emergency fund
     */
    public function show(Chama $chama)
    {
        $this->authorize('view', $chama);

        $fund = $chama->emergencyFund;
        if (!$fund) {
            return response()->json(['error' => 'Emergency fund not found'], 404);
        }

        return response()->json([
            'fund' => $fund,
            'available_balance' => $fund->getAvailableBalance(),
            'is_healthy' => $fund->isHealthy(),
            'recent_withdrawals' => $fund->withdrawals()
                                         ->latest()
                                         ->limit(10)
                                         ->get(),
        ]);
    }

    /**
     * Initialize emergency fund
     */
    public function initialize(Request $request, Chama $chama)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'minimum_balance' => 'required|numeric|min:0',
            'purpose' => 'sometimes|string',
        ]);

        $fund = EmergencyFund::updateOrCreate(
            ['chama_id' => $chama->id],
            $validated
        );

        return response()->json($fund);
    }

    /**
     * Add funds to emergency fund
     */
    public function addFunds(Request $request, Chama $chama)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $fund = $chama->emergencyFund;
        if (!$fund) {
            return response()->json(['error' => 'Emergency fund not initialized'], 400);
        }

        $fund->addFunds($validated['amount']);

        return response()->json(['message' => 'Funds added', 'fund' => $fund]);
    }

    /**
     * Request emergency withdrawal
     */
    public function requestWithdrawal(Request $request, Chama $chama)
    {
        $this->authorize('request-emergency-fund', $chama);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
            'description' => 'sometimes|string',
        ]);

        $fund = $chama->emergencyFund;
        if (!$fund) {
            return response()->json(['error' => 'Emergency fund not found'], 404);
        }

        if ($validated['amount'] > $fund->getAvailableBalance()) {
            return response()->json(['error' => 'Insufficient funds'], 400);
        }

        $withdrawal = EmergencyWithdrawal::create([
            'emergency_fund_id' => $fund->id,
            'user_id' => auth()->id(),
            'chama_id' => $chama->id,
            ...$validated,
        ]);

        return response()->json($withdrawal, 201);
    }

    /**
     * Get withdrawal details
     */
    public function getWithdrawal(Chama $chama, EmergencyWithdrawal $withdrawal)
    {
        $this->authorize('view', $withdrawal);
        return response()->json($withdrawal->load(['user', 'approver']));
    }

    /**
     * Approve withdrawal
     */
    public function approveWithdrawal(Request $request, Chama $chama, EmergencyWithdrawal $withdrawal)
    {
        $this->authorize('approve-emergency-withdrawal', $chama);

        $validated = $request->validate([
            'notes' => 'sometimes|string',
        ]);

        $withdrawal->approve($validated['notes'] ?? null);

        return response()->json(['message' => 'Withdrawal approved', 'withdrawal' => $withdrawal]);
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal(Request $request, Chama $chama, EmergencyWithdrawal $withdrawal)
    {
        $this->authorize('approve-emergency-withdrawal', $chama);

        $validated = $request->validate([
            'notes' => 'sometimes|string',
        ]);

        $withdrawal->reject($validated['notes'] ?? null);

        return response()->json(['message' => 'Withdrawal rejected', 'withdrawal' => $withdrawal]);
    }

    /**
     * Process approved withdrawal
     */
    public function processWithdrawal(Chama $chama, EmergencyWithdrawal $withdrawal)
    {
        $this->authorize('manage-chama', $chama);

        try {
            $withdrawal->process();
            return response()->json(['message' => 'Withdrawal processed', 'withdrawal' => $withdrawal]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get pending withdrawals
     */
    public function pendingWithdrawals(Chama $chama)
    {
        $this->authorize('view', $chama);

        $withdrawals = $chama->emergencyFund
                            ->withdrawals()
                            ->where('status', 'pending')
                            ->with(['user', 'approver'])
                            ->get();

        return response()->json($withdrawals);
    }
}
