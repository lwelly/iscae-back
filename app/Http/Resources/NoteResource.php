<?php
// app/Http/Resources/NoteResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'academic_year'   => $this->academic_year,
            'note_controle'   => $this->note_controle,
            'note_examen'     => $this->note_examen,
            'note_rattrapage' => $this->note_rattrapage,
            'note_finale'     => $this->note_finale,
            'is_published'    => $this->is_published,
            'is_passed'       => $this->isPassed(),
            'published_at'    => $this->published_at?->format('Y-m-d'),
            'module'          => [
                'id'          => $this->module?->id,
                'code'        => $this->module?->code,
                'name'        => $this->module?->name,
                'coefficient' => $this->module?->coefficient,
                'credits'     => $this->module?->credits,
            ],
            'semestre'        => [
                'id'    => $this->semestre?->id,
                'code'  => $this->semestre?->code,
                'label' => $this->semestre?->label,
            ],
        ];
    }
}
