<?php
// app/Http/Resources/NotificationResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'title'      => $this->title,
            'body'       => $this->body,
            'data'       => $this->data,
            'channel'    => $this->channel,
            'is_read'    => $this->is_read,
            'read_at'    => $this->read_at?->format('Y-m-d H:i'),
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
