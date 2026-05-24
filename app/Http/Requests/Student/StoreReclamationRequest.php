<?php
// app/Http/Requests/Student/StoreReclamationRequest.php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreReclamationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'module_id'     => ['required', 'integer', 'exists:modules,id'],
            'semestre_id'   => ['required', 'integer', 'exists:semestres,id'],
            'type'          => ['required', 'in:controle,examen,rattrapage'],
            'note_actuelle' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'note_reclamee' => [
                'nullable',
                'numeric',
                'min:0',
                'max:20',
                'required_if:type,controle',
            ],
            'justification' => ['required', 'string', 'min:20', 'max:2000'],
            'attachment'    => [
                'nullable',
                'file',
                'max:10240', // 10 MB en KB
                'mimes:pdf,jpg,jpeg,png,doc,docx',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'module_id.required'        => 'Le module est obligatoire.',
            'module_id.exists'          => 'Le module sélectionné est invalide.',
            'semestre_id.required'      => 'Le semestre est obligatoire.',
            'type.required'             => 'Le type de réclamation est obligatoire.',
            'type.in'                   => 'Le type doit être : controle, examen ou rattrapage.',
            'note_reclamee.required_if' => 'La note réclamée est obligatoire pour une réclamation contrôle.',
            'justification.required'    => 'La justification est obligatoire.',
            'justification.min'         => 'La justification doit contenir au moins 20 caractères.',
            'attachment.max'            => 'Le fichier ne doit pas dépasser 10 MB.',
            'attachment.mimes'          => 'Formats autorisés : PDF, JPG, PNG, DOC, DOCX.',
        ];
    }
}
