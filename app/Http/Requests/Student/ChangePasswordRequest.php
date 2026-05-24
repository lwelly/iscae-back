<?php
// app/Http/Requests/Student/ChangePasswordRequest.php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
            'password'              => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/',
                'different:current_password',
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'password.required'         => 'Le nouveau mot de passe est obligatoire.',
            'password.min'              => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'        => 'La confirmation ne correspond pas.',
            'password.regex'            => 'Le mot de passe doit contenir au moins 1 majuscule et 1 chiffre.',
            'password.different'        => 'Le nouveau mot de passe doit être différent de l\'ancien.',
        ];
    }
}
