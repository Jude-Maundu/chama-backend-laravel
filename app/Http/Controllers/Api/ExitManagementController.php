<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\MemberExitRequest;
use App\Models\MemberNomination;
use App\Models\Contribution;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExitManagementController extends Controller
{
    /**
     * Submit an exit request.
     */
    public function submitExitRequest(Request $request, Chama $chama)
    {
        $request->validate([
            'reason' => 'required|string',
            'comments' => 'nullable|string',
        ]);

        // Check if there is already a pending request
        if (MemberExitRequest::where('user_id', Auth::id())->where('chama_id', $chama->id)->where('status', 'pending')->exists()) {
            return response()->json(['success' => false, 'message' => 'You already have a pending exit request'], 400);
        }

        // Calculate potential refund
        $totalContributions = Contribution::where('user_id', Auth::id())->where('chama_id', $chama->id)->where('status', 'completed')->sum('amount');
        $outstandingLoans = Loan::where('user_id', Auth::id())->where('chama_id', $chama->id)->where('status', 'active')->sum('balance');
        $refundAmount = max(0, $totalContributions - $outstandingLoans);

        $exitRequest = MemberExitRequest::create([
            'user_id' => Auth::id(),
            'chama_id' => $chama->id,
            'reason' => $request->reason,
            'comments' => $request->comments,
            'status' => 'pending',
            'refund_amount' => $refundAmount,
            'refund_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $exitRequest,
            'calculation' => [
                'total_contributions' => $totalContributions,
                'outstanding_loans' => $outstandingLoans,
                'projected_refund' => $refundAmount
            ]
        ], 201);
    }

    /**
     * Get exit requests for a chama (Admin).
     */
    public function getExitRequests(Chama $chama)
    {
        // Add authorization check here if needed
        $requests = MemberExitRequest::where('chama_id', $chama->id)
                                    ->with('user')
                                    ->orderByDesc('created_at')
                                    ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Approve or reject an exit request.
     */
    public function processExitRequest(Request $request, Chama $chama, MemberExitRequest $exitRequest)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'approval_notes' => 'nullable|string',
        ]);

        $exitRequest->update([
            'status' => $request->status,
            'approved_by' => Auth::id(),
            'approval_notes' => $request->approval_notes,
            'approved_at' => now(),
            'processed_at' => $request->status === 'approved' ? now() : null,
        ]);

        if ($request->status === 'approved') {
            // Further logic for actual payout processing would go here
            // e.g., creating a transaction
            $exitRequest->update(['refund_status' => 'processed']);
        }

        return response()->json([
            'success' => true,
            'data' => $exitRequest
        ]);
    }

    /**
     * Nominations
     */
    public function getNominations(Chama $chama)
    {
        $nominations = MemberNomination::where('nominating_member_id', Auth::id())
                                      ->where('chama_id', $chama->id)
                                      ->get();

        return response()->json([
            'success' => true,
            'data' => $nominations
        ]);
    }

    public function storeNomination(Request $request, Chama $chama)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'allocation_percentage' => 'required|numeric|min:0|max:100',
        ]);

        // Check total percentage for the user in this chama
        $currentTotal = MemberNomination::where('user_id', Auth::id())->where('chama_id', $chama->id)->sum('allocation_percentage');
        
        if ($currentTotal + $request->allocation_percentage > 100) {
            return response()->json([
                'success' => false, 
                'message' => 'Total allocation percentage cannot exceed 100%. Current total: ' . $currentTotal . '%'
            ], 400);
        }

        $nomination = MemberNomination::create([
            'user_id' => Auth::id(),
            'chama_id' => $chama->id,
            'name' => $request->name,
            'relationship' => $request->relationship,
            'phone' => $request->phone,
            'allocation_percentage' => $request->allocation_percentage,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'data' => $nomination
        ], 201);
    }

    public function deleteNomination(Chama $chama, MemberNomination $nomination)
    {
        if ($nomination->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $nomination->delete();

        return response()->json(['success' => true, 'message' => 'Nomination deleted']);
    }
}
