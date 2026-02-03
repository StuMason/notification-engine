<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched after a notification is successfully created.
 *
 * This event exists for future channel expansion (email, push, etc.)
 * without refactoring existing notification creation logic.
 */
class NotificationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Notification $notification,
    ) {}
}
