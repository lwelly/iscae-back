<?php
// app/Http/Resources/ReclamationHistoryResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReclamationHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'old_status'  => $this->old_status,
            'new_status'  => $this->new_status,
            'comment'     => $this->comment,
            'changed_by'  => $this->changedBy?->full_name ?? 'Système',
            'changed_at'  => $this->changed_at->format('Y-m-d H:i'),
        ];
    }
}
