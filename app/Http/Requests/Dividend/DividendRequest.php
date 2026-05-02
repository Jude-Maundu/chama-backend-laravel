<?php

namespace App\Http\Requests\Dividend;

use Illuminate\Foundation\Http\FormRequest;

class DividendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => 'required|string|max:50',
            'total_amount' => 'required|numeric|min:0',
            'per_share_amount' => 'required|numeric|min:0',
        ];
    }
}
