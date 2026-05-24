<?php
// app/Http/Requests/Admin/ScheduleMeetingRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMeetingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'meeting_at' => ['required', 'date', 'after:now'],
            'location'   => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'meeting_at.required' => 'La date de réunion est obligatoire.',
            'meeting_at.after'    => 'La date de réunion doit être dans le futur.',
            'location.required'   => 'Le lieu est obligatoire.',
        ];
    }
}
