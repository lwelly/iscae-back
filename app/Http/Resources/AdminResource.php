<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->user;
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'nom'        => $this->nom,
            'prenom'     => $this->prenom,
            'full_name'  => $this->full_name,
            'role_label' => $this->role_label,
            'role_label_readable' => $this->role_label_readable,
            'two_fa_enabled'      => $this->two_fa_enabled,
            'department'          => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'nom'  => $this->department->nom,
                'code' => $this->department->code,
            ]),
            'user' => $user ? [
                'email'      => $user->email,
                'is_active'  => $user->is_active,
                'last_login' => $user->last_login_at?->format('Y-m-d H:i'),
            ] : null,
        ];
    }
}
