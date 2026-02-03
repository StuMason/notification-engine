<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type->value,
            'entity_type' => $this->entity_type->value,
            'entity_id' => $this->entity_id,
            'deep_link' => $this->deep_link,
            'title' => $this->title,
            'message' => $this->message,
            'context' => $this->context_json ?? (object) [],
            'is_read' => $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
        ];
    }
}
