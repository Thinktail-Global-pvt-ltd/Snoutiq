<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    /**
     * @param  \App\Models\Alert  $resource
     */
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'metadata' => $this->metadata ?: (object) [],
            'isRead' => (bool) $this->is_read,
            'timestamp' => optional($this->created_at)->toIso8601String(),
        ];
    }
}

