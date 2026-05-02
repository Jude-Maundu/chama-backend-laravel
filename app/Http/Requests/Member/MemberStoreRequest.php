<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MemberStoreRequest extends FormRequest
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
            'national_id' => 'required|unique:profiles',
            'password' => 'required|min:8|confirmed',
            'gender' => 'nullable|in:male,female',
            'occupation' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|regex:/^254[0-9]{9}$/',
        ];
    }
}
