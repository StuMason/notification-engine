<?php

declare(strict_types=1);

namespace App\Enums;

enum EntityType: string
{
    case Task = 'task';
    case Meeting = 'meeting';
    case ChatMessage = 'chat_message';
    case VideoRoom = 'video_room';
    case System = 'system';
}
