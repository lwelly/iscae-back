<?php
// app/Http/Requests/Admin/StoreNoteRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Tableau de notes obligatoire
            'notes'                       => ['required', 'array', 'min:1'],

            // Chaque note doit avoir student_id, module_id, semestre_id
            'notes.*.student_id'          => [
                'required',
                'integer',
                'exists:students,id',
            ],
            'notes.*.module_id'           => [
                'required',
                'integer',
                'exists:modules,id',
            ],
            'notes.*.semestre_id'         => [
                'required',
                'integer',
                'exists:semestres,id',
            ],

            // Notes entre 0 et 20
            'notes.*.note_controle'       => [
                'nullable',
                'numeric',
                'min:0',
                'max:20',
            ],
            'notes.*.note_examen'         => [
                'nullable',
                'numeric',
                'min:0',
                'max:20',
            ],
            'notes.*.note_rattrapage'     => [
                'nullable',
                'numeric',
                'min:0',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required'                   => 'La liste des notes est obligatoire.',
            'notes.array'                      => 'Le format des notes est invalide.',
            'notes.min'                        => 'Au moins une note doit être fournie.',
            'notes.*.student_id.required'      => 'L\'identifiant étudiant est obligatoire.',
            'notes.*.student_id.exists'        => 'Un étudiant spécifié est introuvable.',
            'notes.*.module_id.required'       => 'L\'identifiant module est obligatoire.',
            'notes.*.module_id.exists'         => 'Un module spécifié est introuvable.',
            'notes.*.semestre_id.required'     => 'L\'identifiant semestre est obligatoire.',
            'notes.*.semestre_id.exists'       => 'Un semestre spécifié est introuvable.',
            'notes.*.note_controle.numeric'    => 'La note de contrôle doit être un nombre.',
            'notes.*.note_controle.min'        => 'La note de contrôle ne peut pas être négative.',
            'notes.*.note_controle.max'        => 'La note de contrôle ne peut pas dépasser 20.',
            'notes.*.note_examen.numeric'      => 'La note d\'examen doit être un nombre.',
            'notes.*.note_examen.min'          => 'La note d\'examen ne peut pas être négative.',
            'notes.*.note_examen.max'          => 'La note d\'examen ne peut pas dépasser 20.',
            'notes.*.note_rattrapage.numeric'  => 'La note de rattrapage doit être un nombre.',
            'notes.*.note_rattrapage.min'      => 'La note de rattrapage ne peut pas être négative.',
            'notes.*.note_rattrapage.max'      => 'La note de rattrapage ne peut pas dépasser 20.',
        ];
    }

    /**
     * Validation supplémentaire après les règles de base
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $notes = $this->input('notes', []);

            foreach ($notes as $index => $note) {
                // Vérifier que module appartient au semestre
                if (!empty($note['module_id']) && !empty($note['semestre_id'])) {
                    $module = \App\Models\Module::find($note['module_id']);
                    if ($module && $module->semestre_id !== (int) $note['semestre_id']) {
                        $validator->errors()->add(
                            "notes.{$index}.module_id",
                            "Le module sélectionné n'appartient pas au semestre indiqué."
                        );
                    }
                }

                // Vérifier que module appartient à la filière de l'étudiant
                if (!empty($note['module_id']) && !empty($note['student_id'])) {
                    $student = \App\Models\Student::find($note['student_id']);
                    $module  = \App\Models\Module::find($note['module_id']);
                    if ($student && $module && $module->filiere_id !== $student->filiere_id) {
                        $validator->errors()->add(
                            "notes.{$index}.module_id",
                            "Le module ne correspond pas à la filière de l'étudiant."
                        );
                    }
                }
            }
        });
    }
}
