<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReclamationResource extends JsonResource
{
    public function toArray($request): array
    {
        $studentName = trim(
            ($this->student?->prenom ?? '') . ' ' .
            ($this->student?->nom ?? '')
        ) ?: ($this->student?->full_name ?? 'Inconnu');

        return [
            'id'               => $this->id,
            'reference'        => $this->reference_number,
            'reference_number' => $this->reference_number,
            'type'             => $this->mapType($this->type),
            'status'           => $this->status,
            'academic_year'    => $this->academic_year,
            'note_actuelle'    => $this->note_actuelle,
            'note_reclamee'    => $this->note_reclamee,
            'justification'    => $this->justification,
            'admin_response'   => $this->admin_response,
            'is_escalated'     => (bool) ($this->is_escalated ?? false),
            'meeting_at'       => $this->safeDate($this->meeting_scheduled_at),
            'meeting_location' => $this->meeting_location ?? null,
            'resolved_at'      => $this->safeDate($this->resolved_at),
            'created_at'       => $this->safeDate($this->created_at),
            'updated_at'       => $this->safeDate($this->updated_at),

            'module' => [
                'id'   => $this->module?->id,
                'code' => $this->module?->code ?? '—',
                'name' => $this->module?->name ?? '—',
            ],

            'semestre' => [
                'id'    => $this->semestre?->id,
                'code'  => $this->semestre?->code ?? '—',
                'label' => $this->semestre?->label ?? '—',
            ],

            'student' => [
                'id'             => $this->student?->id,
                'student_number' => $this->student?->matricule ?? '—',
                'matricule'      => $this->student?->matricule ?? '—',
                'full_name'      => $studentName,
                'first_name'     => $this->student?->prenom ?? '',
                'last_name'      => $this->student?->nom ?? '',
                'email'          => $this->student?->email
                                 ?? $this->student?->user?->email
                                 ?? '—',
                'filiere' => [
                    'id'   => $this->student?->filiere?->id,
                    'name' => $this->student?->filiere?->name
                           ?? $this->student?->filiere?->code
                           ?? '—',
                ],
                'niveau' => [
                    'id'   => $this->student?->niveau?->id,
                    'name' => $this->student?->niveau?->label
                           ?? $this->student?->niveau?->name
                           ?? $this->student?->niveau?->code
                           ?? '—',
                ],
            ],

            'attachments' => $this->whenLoaded('attachments', function () use ($request) {
                return $this->attachments->map(fn($a) => [
                    'id'         => $a->id,
                    'name'       => $a->original_name ?? 'fichier',
                    'url'        => Storage::url($a->storage_path ?? ''),
                    'mime_type'  => $a->mime_type,
                    'file_size'  => $a->file_size,
                    'created_at' => $this->safeDate($a->created_at),
                ]);
            }),

            'history' => $this->whenLoaded('history', function () use ($request) {
                return $this->history->map(fn($h) => [
                    'id'         => $h->id,
                    'old_status' => $h->old_status,
                    'new_status' => $h->new_status,
                    'comment'    => $h->comment,
                    'created_at' => $this->safeDate($h->created_at),
                    'user'       => [
                        'name' => $h->changedBy?->name
                               ?? $h->changedBy?->email
                               ?? 'Système',
                    ],
                ]);
            }),
        ];
    }

    private function safeDate(mixed $date): ?string
    {
        if ($date === null) {
            return null;
        }
        try {
            return Carbon::parse($date)->format('Y-m-d H:i');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mapType(?string $type): string
    {
        return match($type) {
            'controle'   => 'cc',
            'examen'     => 'examen',
            'rattrapage' => 'rattrapage',
            default      => $type ?? '—',
        };
    }
}
