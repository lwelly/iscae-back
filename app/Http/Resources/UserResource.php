<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,        // accesseur calculé
            'email'  => $this->email,
            'role'   => $this->role,
            'status' => $this->status,      // accesseur calculé
        ];
    }
}
