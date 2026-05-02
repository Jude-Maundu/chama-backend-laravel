<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'agenda' => 'required|string',
            'meeting_date' => 'required|date|after:now',
            'venue' => 'required|string|max:255',
            'virtual_link' => 'nullable|url',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
        ];
    }

    public function messages(): array
    {
        return [
            'meeting_date.after' => 'Meeting date must be in the future',
            'virtual_link.url' => 'Please provide a valid virtual meeting link',
        ];
    }
}
