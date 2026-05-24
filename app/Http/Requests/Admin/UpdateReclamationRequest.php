<?php
// app/Http/Requests/Admin/UpdateReclamationRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReclamationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'         => ['required', 'in:received,in_review,resolved,rejected,escalated'],
            'admin_response' => ['nullable', 'string', 'min:5', 'max:2000'],
            'comment'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in'       => 'Statut invalide.',
        ];
    }
}
