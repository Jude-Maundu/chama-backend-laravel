<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class LoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_type' => 'required|in:emergency,development,education,welfare',
            'amount' => 'required|numeric|min:1000|max:500000',
            'duration_months' => 'required|integer|min:1|max:36',
            'purpose' => 'required|string|min:10|max:500',
            'guarantors' => 'nullable|array',
            'guarantors.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Loan amount must be at least 1,000',
            'amount.max' => 'Loan amount cannot exceed 500,000',
            'purpose.min' => 'Please provide a detailed purpose (minimum 10 characters)',
        ];
    }
}
