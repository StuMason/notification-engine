<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoRoomStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
}
