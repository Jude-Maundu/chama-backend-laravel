<?php

namespace App\Http\Requests\Mpesa;

use Illuminate\Foundation\Http\FormRequest;

class StkPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:1|max:150000',
            'account_reference' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must start with 254 and be 12 digits',
            'amount.max' => 'Amount cannot exceed 150,000 per transaction',
        ];
    }
}
