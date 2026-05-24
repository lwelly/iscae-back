<?php
// app/Http/Resources/ModuleResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'coefficient' => $this->coefficient,
            'credits'     => $this->credits,
            'is_active'   => $this->is_active,

            // Filière parente
            'filiere'     => $this->when(
                $this->relationLoaded('filiere') && $this->filiere,
                fn() => [
                    'id'   => $this->filiere->id,
                    'name' => $this->filiere->name,
                    'code' => $this->filiere->code,
                ]
            ),

            // Semestre parent
            'semestre'    => $this->when(
                $this->relationLoaded('semestre') && $this->semestre,
                fn() => [
                    'id'    => $this->semestre->id,
                    'code'  => $this->semestre->code,
                    'label' => $this->semestre->label,
                ]
            ),
        ];
    }
}
