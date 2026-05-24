<?php
// app/Http/Requests/Auth/Verify2FARequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Verify2FARequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'otp_code'     => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'trust_device' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'  => 'L\'identifiant utilisateur est obligatoire.',
            'otp_code.required' => 'Le code OTP est obligatoire.',
            'otp_code.size'     => 'Le code OTP doit contenir exactement 6 chiffres.',
        ];
    }
}
