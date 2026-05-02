<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamaExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'chama_id', 'expense_category_id', 'created_by', 'description',
        'amount', 'currency_id', 'expense_date', 'status', 'approved_by',
        'approval_notes', 'receipt_path', 'receipt_url', 'attachments'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'attachments' => 'array',
    ];

    public function chama(): BelongsTo
    {
        return $this->belongsTo(Chama::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function approve($notes = null)
    {
        $this->status = 'approved';
        $this->approved_by = auth()->id();
        $this->approval_notes = $notes;
        $this->save();
    }

    public function reject($notes = null)
    {
        $this->status = 'rejected';
        $this->approved_by = auth()->id();
        $this->approval_notes = $notes;
        $this->save();
    }
}
