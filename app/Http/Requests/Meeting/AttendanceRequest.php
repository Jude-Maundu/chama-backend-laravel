<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,excused,late',
            'excuse_reason' => 'required_if:status,excused|string|min:10',
        ];
    }

    public function messages(): array
    {
        return [
            'excuse_reason.required_if' => 'Please provide a reason for being excused',
        ];
    }
}
