<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class LoanRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:mpesa,cash,bank',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Repayment amount must be at least 100',
        ];
    }
}
