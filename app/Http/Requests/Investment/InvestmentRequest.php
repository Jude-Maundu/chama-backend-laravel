<?php

namespace App\Http\Requests\Investment;

use Illuminate\Foundation\Http\FormRequest;

class InvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:shares,real_estate,fixed_deposit,business,saccco,treasury_bills',
            'amount_invested' => 'required|numeric|min:1000',
            'expected_return_rate' => 'required|numeric|min:0|max:100',
            'investment_date' => 'required|date',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'amount_invested.min' => 'Investment amount must be at least 1,000',
        ];
    }
}
