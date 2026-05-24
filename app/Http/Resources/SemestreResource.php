<?php
// app/Http/Resources/SemestreResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SemestreResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'label'         => $this->label,
            'order_index'   => $this->order_index,
            'academic_year' => $this->academic_year,
            'is_open'       => $this->is_open,
            'is_active'     => $this->isCurrentlyOpen(),
            'open_at'       => $this->open_at?->format('Y-m-d'),
            'close_at'      => $this->close_at?->format('Y-m-d'),

            // Niveau parent
            'niveau'        => $this->when(
                $this->relationLoaded('niveau') && $this->niveau,
                fn() => [
                    'id'    => $this->niveau->id,
                    'code'  => $this->niveau->code,
                    'label' => $this->niveau->label,
                ]
            ),

            // Modules du semestre (optionnel)
            'modules'       => ModuleResource::collection(
                $this->whenLoaded('modules')
            ),

            // Stats (visibles côté admin uniquement)
            'stats'         => $this->when(
                $request->is('api/v1/admin/*'),
                fn() => [
                    'modules_count'      => $this->modules_count ?? $this->modules()->count(),
                    'reclamations_count' => $this->reclamations()->count(),
                ]
            ),
        ];
    }
}
