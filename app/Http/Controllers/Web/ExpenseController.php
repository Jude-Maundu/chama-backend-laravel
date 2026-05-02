<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChamaExpense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $expenses = ChamaExpense::where('chama_id', $user->chama_id)
            ->orderBy('date', 'desc')
            ->paginate(15);

        $totalExpenses = ChamaExpense::where('chama_id', $user->chama_id)->sum('amount');
        $monthExpenses = ChamaExpense::where('chama_id', $user->chama_id)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount');
        $pendingExpenses = ChamaExpense::where('chama_id', $user->chama_id)
            ->where('status', 'pending')
            ->count();

        $budgetRemaining = 500000 - $monthExpenses; // Placeholder budget

        return view('expenses.index', compact('expenses', 'totalExpenses', 'monthExpenses', 'pendingExpenses', 'budgetRemaining'));
    }

    public function create()
    {
        $categories = ExpenseCategory::all();
        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:expense_categories,id',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'paid_to' => 'required|string|max:255',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'notes' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('expenses', 'public');
            $validated['receipt_path'] = $path;
        }

        $expense = ChamaExpense::create([
            'chama_id' => $user->chama_id,
            'category_id' => $validated['category_id'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'paid_to' => $validated['paid_to'],
            'date' => $validated['date'],
            'recorded_by' => $user->id,
            'status' => 'pending',
            'receipt_path' => $validated['receipt_path'] ?? null,
            'notes' => $validated['notes'] ?? null
        ]);

        return redirect()->route('expenses.show', $expense->id)
            ->with('success', 'Expense recorded successfully and sent for approval');
    }

    public function show(ChamaExpense $expense)
    {
        $user = Auth::user();
        if ($expense->chama_id !== $user->chama_id) {
            abort(403);
        }

        return view('expenses.show', compact('expense'));
    }

    public function edit(ChamaExpense $expense)
    {
        $user = Auth::user();
        if ($expense->chama_id !== $user->chama_id || $expense->status !== 'pending') {
            abort(403);
        }

        $categories = ExpenseCategory::all();
        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, ChamaExpense $expense)
    {
        $user = Auth::user();
        if ($expense->chama_id !== $user->chama_id || $expense->status !== 'pending') {
            abort(403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:expense_categories,id',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'paid_to' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.show', $expense->id)
            ->with('success', 'Expense updated successfully');
    }

    public function destroy(ChamaExpense $expense)
    {
        $user = Auth::user();
        if ($expense->chama_id !== $user->chama_id || $expense->status !== 'pending') {
            abort(403);
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully');
    }

    public function approve(ChamaExpense $expense)
    {
        $user = Auth::user();
        if ($user->role !== 'treasurer') {
            abort(403);
        }

        $expense->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Expense approved');
    }

    public function reject(Request $request, ChamaExpense $expense)
    {
        $user = Auth::user();
        if ($user->role !== 'treasurer') {
            abort(403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $expense->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason']
        ]);

        return redirect()->back()->with('success', 'Expense rejected');
    }
}
