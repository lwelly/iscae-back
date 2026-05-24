<?php
// app/Http/Requests/Admin/BulkNotificationRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkNotificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Cible de l'envoi
            'target'        => [
                'required',
                'in:all_students,filiere,niveau,specific',
            ],

            // Obligatoire si target = filiere
            'filiere_id'    => [
                'nullable',
                'integer',
                'exists:filieres,id',
                'required_if:target,filiere',
            ],

            // Obligatoire si target = niveau
            'niveau_id'     => [
                'nullable',
                'integer',
                'exists:niveaux,id',
                'required_if:target,niveau',
            ],

            // Obligatoire si target = specific
            'user_ids'      => [
                'nullable',
                'array',
                'required_if:target,specific',
            ],
            'user_ids.*'    => [
                'integer',
                'exists:users,id',
            ],

            // Contenu de la notification
            'title'         => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'body'          => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'channel'       => [
                'required',
                'in:in_app,email,both',
            ],

            // Type personnalisé (optionnel)
            'type'          => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target.required'           => 'La cible de la notification est obligatoire.',
            'target.in'                 => 'La cible doit être : all_students, filiere, niveau ou specific.',
            'filiere_id.required_if'    => 'La filière est obligatoire quand la cible est "filiere".',
            'filiere_id.exists'         => 'La filière sélectionnée est introuvable.',
            'niveau_id.required_if'     => 'Le niveau est obligatoire quand la cible est "niveau".',
            'niveau_id.exists'          => 'Le niveau sélectionné est introuvable.',
            'user_ids.required_if'      => 'La liste des utilisateurs est obligatoire pour un envoi spécifique.',
            'user_ids.*.exists'         => 'Un ou plusieurs utilisateurs spécifiés sont introuvables.',
            'title.required'            => 'Le titre de la notification est obligatoire.',
            'title.min'                 => 'Le titre doit contenir au moins 3 caractères.',
            'title.max'                 => 'Le titre ne peut pas dépasser 255 caractères.',
            'body.required'             => 'Le contenu de la notification est obligatoire.',
            'body.min'                  => 'Le contenu doit contenir au moins 10 caractères.',
            'body.max'                  => 'Le contenu ne peut pas dépasser 2000 caractères.',
            'channel.required'          => 'Le canal d\'envoi est obligatoire.',
            'channel.in'                => 'Le canal doit être : in_app, email ou both.',
        ];
    }
}
