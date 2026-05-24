<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'registration_token'    => ['required', 'string'],
            'password'              => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/',
            ],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_token.required' => 'Token d\'inscription manquant.',
            'password.required'           => 'Le mot de passe est obligatoire.',
            'password.min'                => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'          => 'La confirmation du mot de passe ne correspond pas.',
            'password.regex'              => 'Le mot de passe doit contenir au moins 1 majuscule et 1 chiffre.',
        ];
    }
}
