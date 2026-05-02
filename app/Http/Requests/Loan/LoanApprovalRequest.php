<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class LoanApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|min:10',
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'Please provide a reason for rejection',
        ];
    }
}
