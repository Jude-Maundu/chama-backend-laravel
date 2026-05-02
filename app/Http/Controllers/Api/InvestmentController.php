<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Investment;
use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    public function index()
    {
        $investments = Investment::with('creator')->latest()->paginate(10);
        $totalInvested = $investments->sum('amount_invested');
        $currentValue = $investments->sum('current_value');
        $roi = $totalInvested > 0 ? (($currentValue - $totalInvested) / $totalInvested) * 100 : 0;
        
        return response()->json([
            'investments' => $investments,
            'summary' => [
                'total_invested' => $totalInvested,
                'current_value' => $currentValue,
                'roi_percentage' => $roi
            ]
        ]);
    }

    public function create()
    {
        return response()->json([
            'success' => true,
            'message' => 'Use store endpoint to create investments'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'investment_type' => 'required|string',
            'amount_invested' => 'required|numeric|min:1',
            'expected_return_rate' => 'nullable|numeric|min:0|max:100',
            'current_value' => 'required|numeric|min:0',
            'investment_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $investment = Investment::create([
            'name' => $request->name ?? $request->investment_type,
            'type' => $request->investment_type,
            'amount_invested' => $request->amount_invested,
            'current_value' => $request->current_value,
            'expected_return_rate' => $request->expected_return_rate ?? 0,
            'investment_date' => $request->investment_date,
            'maturity_date' => $request->maturity_date,
            'description' => $request->description,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        // Record transaction
        Transaction::create([
            'user_id' => Auth::id(),
            'type' => 'expense',
            'direction' => 'debit',
            'amount' => $request->amount_invested,
            'description' => "Investment in {$investment->name}",
            'transaction_date' => now(),
            'created_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_investment',
            'table_name' => 'investments',
            'record_id' => $investment->id,
            'new_values' => json_encode($request->all()),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Investment recorded successfully',
            'data' => $investment
        ], 201);
    }

    public function show($id)
    {
        $investment = Investment::with('creator')->findOrFail($id);
        $roi = $investment->return_on_investment;
        
        return response()->json([
            'success' => true,
            'data' => [
                'investment' => $investment,
                'roi' => $roi
            ]
        ]);
    }

    public function edit($id)
    {
        $investment = Investment::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $investment
        ]);
    }

    public function update(Request $request, $id)
    {
        $investment = Investment::findOrFail($id);
        
        $request->validate([
            'name' => 'nullable|string|max:255',
            'investment_type' => 'required|string',
            'current_value' => 'required|numeric',
            'status' => 'nullable|in:active,matured,sold,loss,withdrawn',
        ]);

        $investment->update([
            'name' => $request->name ?? $investment->name,
            'type' => $request->investment_type,
            'current_value' => $request->current_value,
            'status' => $request->status ?? $investment->status,
            'maturity_date' => $request->maturity_date ?? $investment->maturity_date
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_investment',
            'table_name' => 'investments',
            'record_id' => $investment->id,
            'new_values' => json_encode($request->all()),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Investment updated successfully',
            'data' => $investment
        ]);
    }

    public function performance()
    {
        $investments = Investment::all();
        
        $performance = [
            'total_invested' => $investments->sum('amount_invested'),
            'current_value' => $investments->sum('current_value'),
            'total_return' => $investments->sum('current_value') - $investments->sum('amount_invested'),
            'roi_percentage' => $investments->sum('amount_invested') > 0 
                ? (($investments->sum('current_value') - $investments->sum('amount_invested')) / $investments->sum('amount_invested')) * 100 
                : 0,
            'by_type' => $investments->groupBy('type')->map(function ($group) {
                return [
                    'invested' => $group->sum('amount_invested'),
                    'current' => $group->sum('current_value'),
                    'roi' => $group->sum('amount_invested') > 0 
                        ? (($group->sum('current_value') - $group->sum('amount_invested')) / $group->sum('amount_invested')) * 100 
                        : 0,
                ];
            }),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'investments' => $investments,
                'performance' => $performance
            ]
        ]);
    }

    public function vote(Request $request, $id)
    {
        // Voting logic for new investments (requires member voting)
        $investment = Investment::findOrFail($id);
        
        // Record vote in meeting_votes table or separate investment_votes table
        // Requires 70% approval for new investments
        
        return response()->json(['success' => true]);
    }
}