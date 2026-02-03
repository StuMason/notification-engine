<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\VideoRoom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
