<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EntityType;
use App\Enums\EventType;
use App\Events\NotificationCreated;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private readonly NotificationDeduplicator $deduplicator,
        private readonly DeepLinkResolver $deepLinkResolver,
    ) {}

    /**
     * Create notifications for the given recipients.
     *
     * This is the SINGLE entry point for all notification creation.
     * Enforces: no-self-notification, deduplication, tenant isolation, fan-out.
     *
     * @param  Hotel  $hotel  The hotel (tenant) context
     * @param  Collection<int, User>|array<User|string>  $recipients  User models or user IDs
     * @param  EventType  $eventType  The type of event triggering this notification
     * @param  Model  $entity  The source entity that caused the event
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message body
     * @param  string|null  $deepLink  URL path for click-through (auto-resolved if null)
     * @param  array<string, mixed>  $context  Additional structured data for rendering
     * @param  User|null  $actor  The user who caused the event (excluded from recipients)
     * @return int The count of notifications created
     */
    public function notify(
        Hotel $hotel,
        Collection|array $recipients,
        EventType $eventType,
        Model $entity,
        string $title,
        string $message,
        ?string $deepLink = null,
        array $context = [],
        ?User $actor = null,
    ): int {
        $recipients = $this->resolveRecipients($recipients);
        $entityType = $this->deepLinkResolver->resolveEntityType($entity);
        $resolvedDeepLink = $deepLink ?? $this->deepLinkResolver->resolve($entity);

        // Rule 1: No-self-notification — remove actor from recipients
        if ($actor !== null) {
            $recipients = $recipients->reject(fn (User $user) => $user->getKey() === $actor->getKey());
        }

        $created = 0;

        DB::transaction(function () use (
            $hotel, $recipients, $eventType, $entityType, $entity, $title, $message, $resolvedDeepLink, $context, &$created
        ) {
            /** @var User $recipient */
            foreach ($recipients as $recipient) {
                // Rule 2: Deduplication — skip if identical notification exists within window
                if ($this->deduplicator->isDuplicate(
                    hotelId: $hotel->getKey(),
                    userId: $recipient->getKey(),
                    eventType: $eventType->value,
                    entityType: $entityType->value,
                    entityId: $entity->getKey(),
                )) {
                    Log::debug('Notification deduplicated', [
                        'hotel_id' => $hotel->getKey(),
                        'user_id' => $recipient->getKey(),
                        'event_type' => $eventType->value,
                        'entity_id' => $entity->getKey(),
                    ]);

                    continue;
                }

                // Rule 3: Tenant isolation — every notification has hotel_id
                // Rule 4: Fan-out — one row per recipient
                $notification = Notification::create([
                    'hotel_id' => $hotel->getKey(),
                    'user_id' => $recipient->getKey(),
                    'event_type' => $eventType->value,
                    'entity_type' => $entityType->value,
                    'entity_id' => $entity->getKey(),
                    'deep_link' => $resolvedDeepLink,
                    'title' => $title,
                    'message' => $message,
                    'context_json' => $context ?: null,
                    'is_read' => false,
                ]);

                // Dispatch event for future channel expansion
                NotificationCreated::dispatch($notification);

                $created++;
            }
        });

        Log::info('Notifications created', [
            'hotel_id' => $hotel->getKey(),
            'event_type' => $eventType->value,
            'entity_id' => $entity->getKey(),
            'recipients_count' => $created,
        ]);

        return $created;
    }

    /**
     * Resolve mixed recipients into a collection of User models.
     *
     * @param  Collection<int, User>|array<User|string>  $recipients
     * @return Collection<int, User>
     */
    private function resolveRecipients(Collection|array $recipients): Collection
    {
        $collection = Collection::wrap($recipients);

        // If any elements are strings (UUIDs), load them
        if ($collection->first() && is_string($collection->first())) {
            return User::query()->whereIn('id', $collection->all())->get();
        }

        return $collection;
    }
}
