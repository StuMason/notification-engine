<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

class NotificationDeduplicator
{
    /**
     * Check if a duplicate notification exists within the configured time window.
     */
    public function isDuplicate(
        string $hotelId,
        string $userId,
        string $eventType,
        string $entityType,
        string $entityId,
    ): bool {
        $windowMinutes = (int) config('notifications.dedup_window_minutes', 5);

        return Notification::query()
            ->where('hotel_id', $hotelId)
            ->where('user_id', $userId)
            ->where('event_type', $eventType)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->exists();
    }
}
