<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users|regex:/^254[0-9]{9}$/',
            'password' => 'required|min:8|confirmed',
            'national_id' => 'required|unique:profiles',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must start with 254 and be 12 digits',
            'national_id.unique' => 'This National ID is already registered',
        ];
    }
}
