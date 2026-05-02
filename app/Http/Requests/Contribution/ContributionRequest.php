<?php

namespace App\Http\Requests\Contribution;

use Illuminate\Foundation\Http\FormRequest;

class ContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:mpesa,cash,bank_transfer,cheque',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Contribution amount must be at least 100',
            'user_id.exists' => 'Selected member does not exist',
        ];
    }
}
