<?php

namespace App\Http\Controllers\Api;

use App\Models\BankReconciliation;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BankReconciliationController extends Controller
{
    /**
     * Get reconciliations for Chama
     */
    public function index(Chama $chama)
    {
        $this->authorize('view', $chama);

        $reconciliations = $chama->bankReconciliations()
                                ->with(['uploadedBy', 'reviewedBy'])
                                ->latest()
                                ->paginate(20);

        return response()->json($reconciliations);
    }

    /**
     * Upload bank statement
     */
    public function upload(Request $request, Chama $chama)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,pdf,xlsx|max:10240',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $file = $validated['file'];
        $filePath = $file->store('bank-statements/' . $chama->id, 'private');

        $reconciliation = $chama->bankReconciliations()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'statement_date' => $validated['statement_date'],
            'statement_balance' => $validated['statement_balance'],
            'system_balance' => $chama->getAccountBalance(),
            'uploaded_by' => auth()->id(),
            'status' => 'uploaded',
        ]);

        // Dispatch job to process reconciliation
        \App\Jobs\ProcessBankReconciliation::dispatch($reconciliation);

        return response()->json($reconciliation, 201);
    }

    /**
     * Get reconciliation details
     */
    public function show(Chama $chama, BankReconciliation $reconciliation)
    {
        $this->authorize('view', $chama);

        return response()->json($reconciliation->load(['uploadedBy', 'reviewedBy']));
    }

    /**
     * Get reconciliation results
     */
    public function results(Chama $chama, BankReconciliation $reconciliation)
    {
        $this->authorize('view', $chama);

        return response()->json([
            'reconciliation' => $reconciliation,
            'match_percentage' => $reconciliation->getMatchPercentage(),
            'summary' => [
                'statement_balance' => $reconciliation->statement_balance,
                'system_balance' => $reconciliation->system_balance,
                'variance' => $reconciliation->variance,
                'status' => $reconciliation->status,
                'discrepancies' => $reconciliation->discrepancies ?? [],
            ]
        ]);
    }

    /**
     * Review and approve reconciliation
     */
    public function approve(Request $request, Chama $chama, BankReconciliation $reconciliation)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'review_notes' => 'sometimes|string',
        ]);

        $reconciliation->approve();
        $reconciliation->update(['review_notes' => $validated['review_notes'] ?? null]);

        return response()->json(['message' => 'Reconciliation approved', 'reconciliation' => $reconciliation]);
    }

    /**
     * Get discrepancies
     */
    public function discrepancies(Chama $chama, BankReconciliation $reconciliation)
    {
        $this->authorize('view', $chama);

        return response()->json([
            'discrepancies' => $reconciliation->discrepancies ?? [],
            'unmatched_transactions' => $reconciliation->unmatched_transactions,
        ]);
    }
}
