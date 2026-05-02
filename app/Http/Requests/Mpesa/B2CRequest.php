<?php

namespace App\Http\Requests\Mpesa;

use Illuminate\Foundation\Http\FormRequest;

class B2CRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|regex:/^254[0-9]{9}$/',
            'amount' => 'required|numeric|min:10|max:150000',
            'remarks' => 'required|string|max:100',
        ];
    }
}
