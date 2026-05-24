<?php
// app/Http/Resources/DocumentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'title'         => $this->title,
            'mime_type'     => $this->mime_type,
            'size_mb'       => $this->size_in_mb,
            'academic_year' => $this->academic_year,
            'is_published'  => $this->is_published,
            'published_at'  => $this->published_at?->format('Y-m-d'),

            // URL de téléchargement (route protégée)
            'download_url'  => route('documents.download', ['id' => $this->id]),

            // Infos étudiant (visible uniquement côté admin)
            'student'       => $this->when(
                $request->is('api/v1/admin/*') && $this->relationLoaded('student'),
                fn() => [
                    'id'        => $this->student?->id,
                    'matricule' => $this->student?->matricule,
                    'full_name' => $this->student?->full_name,
                ]
            ),

            'created_at'    => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
