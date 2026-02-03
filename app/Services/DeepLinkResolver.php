<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Model;

class DeepLinkResolver
{
    /**
     * Generate a deep link path for a given entity.
     */
    public function resolve(Model $entity): string
    {
        $entityType = $this->resolveEntityType($entity);

        return match ($entityType) {
            EntityType::Task => "/agenda/tasks/{$entity->getKey()}",
            EntityType::Meeting => "/calendar/meetings/{$entity->getKey()}",
            EntityType::ChatMessage => "/chat/messages/{$entity->getKey()}",
            EntityType::VideoRoom => "/video/rooms/{$entity->getKey()}",
            EntityType::System => '/system/alerts',
        };
    }

    /**
     * Determine the EntityType for a given model.
     */
    public function resolveEntityType(Model $entity): EntityType
    {
        return match ($entity::class) {
            \App\Models\Task::class => EntityType::Task,
            \App\Models\Meeting::class => EntityType::Meeting,
            \App\Models\ChatMessage::class => EntityType::ChatMessage,
            \App\Models\VideoRoom::class => EntityType::VideoRoom,
            default => EntityType::System,
        };
    }
}
