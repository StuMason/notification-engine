<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case TaskAssigned = 'task.assigned';
    case TaskOverdue = 'task.overdue';
    case TaskCompleted = 'task.completed';
    case MeetingReminder = 'meeting.reminder';
    case MeetingCancelled = 'meeting.cancelled';
    case ChatMentioned = 'chat.mentioned';
    case VideoStarted = 'video.started';
    case SystemAlert = 'system.alert';

    /**
     * Get the display label for this event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::TaskAssigned => 'Task Assigned',
            self::TaskOverdue => 'Task Overdue',
            self::TaskCompleted => 'Task Completed',
            self::MeetingReminder => 'Meeting Reminder',
            self::MeetingCancelled => 'Meeting Cancelled',
            self::ChatMentioned => 'Chat Mention',
            self::VideoStarted => 'Video Started',
            self::SystemAlert => 'System Alert',
        };
    }

    /**
     * Get the category for this event type.
     */
    public function category(): string
    {
        return match ($this) {
            self::TaskAssigned, self::TaskOverdue, self::TaskCompleted => 'Agenda',
            self::MeetingReminder, self::MeetingCancelled => 'Calendar',
            self::ChatMentioned => 'Chat',
            self::VideoStarted => 'Video',
            self::SystemAlert => 'System',
        };
    }
}
