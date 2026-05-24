<?php
// app/Http/Requests/Auth/SendOtpRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'preloaded_id' => [
                'required',
                'integer',
                'exists:students_preloaded,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'preloaded_id.required' => 'L\'identifiant de pré-inscription est obligatoire.',
            'preloaded_id.integer'  => 'L\'identifiant doit être un nombre entier.',
            'preloaded_id.exists'   => 'Aucun dossier trouvé avec cet identifiant.',
        ];
    }
}
