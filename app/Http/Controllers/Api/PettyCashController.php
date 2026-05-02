<?php

namespace App\Http\Controllers\Api;

use App\Models\PettyCashAccount;
use App\Models\PettyCashClaim;
use App\Models\PettyCashReconciliation;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PettyCashController extends Controller
{
    /**
     * Get petty cash accounts for Chama
     */
    public function index(Chama $chama)
    {
        $this->authorize('view', $chama);

        $accounts = $chama->pettyCashAccounts()
                         ->with('custodian', 'currency')
                         ->get();

        return response()->json($accounts);
    }

    /**
     * Create petty cash account
     */
    public function store(Request $request, Chama $chama)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'currency_id' => 'required|exists:currencies,id',
            'custodian_id' => 'required|exists:users,id',
            'float_amount' => 'required|numeric|min:0',
        ]);

        $account = $chama->pettyCashAccounts()->create([
            ...$validated,
            'balance' => $validated['float_amount'],
        ]);

        return response()->json($account, 201);
    }

    /**
     * Get account details
     */
    public function show(Chama $chama, PettyCashAccount $account)
    {
        $this->authorize('view', $chama);

        return response()->json([
            'account' => $account->load('custodian', 'currency'),
            'approved_claims_total' => $account->getApprovedClaimsTotal(),
            'recent_claims' => $account->claims()
                                       ->where('status', 'paid')
                                       ->latest()
                                       ->limit(5)
                                       ->get(),
        ]);
    }

    /**
     * Submit petty cash claim
     */
    public function submitClaim(Request $request, Chama $chama, PettyCashAccount $account)
    {
        $this->authorize('submit-claim', $account);

        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'receipt_path' => 'sometimes|string',
            'receipt_files' => 'sometimes|array',
        ]);

        $claim = $account->claims()->create([
            'user_id' => auth()->id(),
            ...$validated,
            'status' => 'submitted',
        ]);

        return response()->json($claim, 201);
    }

    /**
     * Get pending claims
     */
    public function pendingClaims(Chama $chama, PettyCashAccount $account)
    {
        $this->authorize('approve-claim', $account);

        $claims = $account->claims()
                         ->where('status', 'submitted')
                         ->with('user')
                         ->get();

        return response()->json($claims);
    }

    /**
     * Approve claim
     */
    public function approveClaim(Request $request, Chama $chama, PettyCashAccount $account, PettyCashClaim $claim)
    {
        $this->authorize('approve-claim', $account);

        $validated = $request->validate([
            'notes' => 'sometimes|string',
        ]);

        $claim->approve($validated['notes'] ?? null);

        return response()->json(['message' => 'Claim approved', 'claim' => $claim]);
    }

    /**
     * Reject claim
     */
    public function rejectClaim(Request $request, Chama $chama, PettyCashAccount $account, PettyCashClaim $claim)
    {
        $this->authorize('approve-claim', $account);

        $claim->update(['status' => 'rejected']);

        return response()->json(['message' => 'Claim rejected', 'claim' => $claim]);
    }

    /**
     * Pay claim
     */
    public function payClaim(Chama $chama, PettyCashAccount $account, PettyCashClaim $claim)
    {
        $this->authorize('manage-chama', $chama);

        try {
            $claim->pay();
            return response()->json(['message' => 'Claim paid', 'claim' => $claim]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Submit reconciliation
     */
    public function submitReconciliation(Request $request, Chama $chama, PettyCashAccount $account)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'physical_count' => 'required|numeric|min:0',
            'variance_explanation' => 'sometimes|string',
            'reconciliation_date' => 'required|date',
        ]);

        $reconciliation = $account->reconciliations()->create([
            'reconciled_by' => auth()->id(),
            'recorded_balance' => $account->balance,
            'variance' => $validated['physical_count'] - $account->balance,
            ...$validated,
            'status' => 'pending',
        ]);

        return response()->json($reconciliation, 201);
    }

    /**
     * Approve reconciliation
     */
    public function approveReconciliation(Chama $chama, PettyCashAccount $account, PettyCashReconciliation $reconciliation)
    {
        $this->authorize('manage-chama', $chama);

        $reconciliation->approve();
        $account->update(['balance' => $reconciliation->physical_count]);

        return response()->json(['message' => 'Reconciliation approved']);
    }
}
