<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class MemberUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->member,
            'phone' => 'required|regex:/^254[0-9]{9}$/|unique:users,phone,' . $this->member,
            'gender' => 'nullable|in:male,female',
            'occupation' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
