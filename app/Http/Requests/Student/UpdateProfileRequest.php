<?php
// app/Http/Requests/Student/UpdateProfileRequest.php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'telephone'      => ['nullable', 'string', 'max:20'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'photo'          => [
                'nullable',
                'image',
                'max:2048', // 2 MB
                'mimes:jpg,jpeg,png',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',
            'photo.image'           => 'Le fichier doit être une image.',
            'photo.max'             => 'La photo ne doit pas dépasser 2 MB.',
            'photo.mimes'           => 'Formats autorisés : JPG, JPEG, PNG.',
        ];
    }
}
