<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\VideoRoom;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VideoRoomStarted
{
    use Dispatchable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\User>|array<\App\Models\User>  $invitedParticipants
     */
    public function __construct(
        public readonly VideoRoom $videoRoom,
        public readonly Collection|array $invitedParticipants,
    ) {}
}
