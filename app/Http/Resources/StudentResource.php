<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'matricule'    => $this->matricule,
            'nom'          => $this->nom,
            'prenom'       => $this->prenom,
            'name'         => $this->full_name,
            'email'        => $this->email,
            'filiere'      => $this->filiere?->code,
            'filiere_nom'  => $this->filiere?->nom ?? $this->filiere?->name,
            'niveau'       => $this->niveau?->code,
            'academic_year'=> $this->academic_year,
            'phone'        => $this->phone,
            'address'      => $this->address,
            'photo_url'    => $this->photo_url,
            'created_at'   => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
