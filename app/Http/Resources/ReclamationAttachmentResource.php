<?php
// app/Http/Resources/ReclamationAttachmentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReclamationAttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'size_mb'       => $this->size_in_mb,
            'is_safe'       => $this->is_safe,
        ];
    }
}
