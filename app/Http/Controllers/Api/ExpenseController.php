<?php

namespace App\Http\Controllers\Api;

use App\Models\ExpenseCategory;
use App\Models\ChamaExpense;
use App\Models\Chama;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ExpenseController extends Controller
{
    /**
     * Get expense categories for Chama
     */
    public function categories(Chama $chama)
    {
        $this->authorize('view', $chama);

        $categories = $chama->expenseCategories()
                           ->orderBy('sort_order')
                           ->get();

        return response()->json($categories);
    }

    /**
     * Create expense category
     */
    public function storeCategory(Request $request, Chama $chama)
    {
        $this->authorize('manage-chama', $chama);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'color' => 'sometimes|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'icon' => 'sometimes|string',
            'budget_limit' => 'sometimes|numeric|min:0',
            'requires_approval' => 'sometimes|boolean',
        ]);

        $category = $chama->expenseCategories()->create($validated);

        return response()->json($category, 201);
    }

    /**
     * Get expenses with filters
     */
    public function index(Request $request, Chama $chama)
    {
        $this->authorize('view', $chama);

        $query = $chama->expenses();

        if ($request->has('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('expense_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('expense_date', '<=', $request->to_date);
        }

        $expenses = $query->with(['category', 'creator', 'approver', 'currency'])
                          ->latest()
                          ->paginate(20);

        return response()->json($expenses);
    }

    /**
     * Create expense
     */
    public function store(Request $request, Chama $chama)
    {
        $this->authorize('create-expense', $chama);

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'expense_date' => 'required|date',
            'receipt_path' => 'sometimes|string',
            'attachments' => 'sometimes|array',
        ]);

        $category = $chama->expenseCategories()->findOrFail($validated['expense_category_id']);

        $expense = $chama->expenses()->create([
            ...$validated,
            'created_by' => auth()->id(),
            'status' => $category->requires_approval ? 'pending_approval' : 'draft',
        ]);

        return response()->json($expense, 201);
    }

    /**
     * Update expense
     */
    public function update(Request $request, Chama $chama, ChamaExpense $expense)
    {
        $this->authorize('update', $expense);

        $validated = $request->validate([
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'status' => 'sometimes|in:draft,pending_approval,approved,rejected',
        ]);

        $expense->update($validated);

        return response()->json($expense);
    }

    /**
     * Submit for approval
     */
    public function submitApproval(Chama $chama, ChamaExpense $expense)
    {
        $this->authorize('update', $expense);

        $expense->update(['status' => 'pending_approval']);

        return response()->json(['message' => 'Expense submitted for approval']);
    }

    /**
     * Approve expense
     */
    public function approve(Request $request, Chama $chama, ChamaExpense $expense)
    {
        $this->authorize('approve-expense', $chama);

        $validated = $request->validate([
            'notes' => 'sometimes|string',
        ]);

        $expense->approve($validated['notes'] ?? null);

        return response()->json(['message' => 'Expense approved', 'expense' => $expense]);
    }

    /**
     * Reject expense
     */
    public function reject(Request $request, Chama $chama, ChamaExpense $expense)
    {
        $this->authorize('approve-expense', $chama);

        $validated = $request->validate([
            'notes' => 'sometimes|string',
        ]);

        $expense->reject($validated['notes'] ?? null);

        return response()->json(['message' => 'Expense rejected', 'expense' => $expense]);
    }

    /**
     * Get expense stats
     */
    public function stats(Chama $chama)
    {
        $this->authorize('view', $chama);

        $thisMonth = $chama->expenses()
                           ->whereMonth('expense_date', now()->month)
                           ->whereYear('expense_date', now()->year)
                           ->sum('amount');

        $lastMonth = $chama->expenses()
                           ->whereMonth('expense_date', now()->subMonth()->month)
                           ->whereYear('expense_date', now()->subMonth()->year)
                           ->sum('amount');

        $trend = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;

        $topCategory = $chama->expenses()
                             ->select('expense_category_id', \DB::raw('SUM(amount) as total'))
                             ->groupBy('expense_category_id')
                             ->with('category')
                             ->orderByDesc('total')
                             ->first();

        return response()->json([
            'thisMonth' => (float)$thisMonth,
            'thisYear' => (float)$chama->expenses()->whereYear('expense_date', now()->year)->sum('amount'),
            'trend' => round($trend, 1),
            'topCategory' => $topCategory ? $topCategory->category->name : 'None',
            'topCategoryAmount' => $topCategory ? (float)$topCategory->total : 0
        ]);
    }
}
