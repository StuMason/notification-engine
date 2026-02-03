<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\UserRole;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemAlertBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<UserRole>  $targetRoles
     */
    public function __construct(
        public readonly Hotel $hotel,
        public readonly string $title,
        public readonly string $message,
        public readonly array $targetRoles,
        public readonly ?User $sender = null,
    ) {}
}
