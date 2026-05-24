<?php
// app/Http/Requests/Auth/VerifyOtpRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'preloaded_id' => ['required', 'integer', 'exists:students_preloaded,id'],
            'otp_code'     => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp_code.required' => 'Le code OTP est obligatoire.',
            'otp_code.size'     => 'Le code OTP doit contenir exactement 6 chiffres.',
            'otp_code.regex'    => 'Le code OTP doit contenir uniquement des chiffres.',
        ];
    }
}
