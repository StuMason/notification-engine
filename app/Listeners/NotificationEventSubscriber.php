<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EventType;
use App\Enums\UserRole;
use App\Events\MeetingCancelled;
use App\Events\MeetingReminder;
use App\Events\SystemAlertBroadcast;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskOverdue;
use App\Events\UserMentionedInChat;
use App\Events\VideoRoomStarted;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Events\Dispatcher;

class NotificationEventSubscriber
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handleTaskAssigned(TaskAssigned $event): void
    {
        $task = $event->task;

        $this->notificationService->notify(
            hotel: $task->hotel,
            recipients: [$task->assignee],
            eventType: EventType::TaskAssigned,
            entity: $task,
            title: 'New Task Assigned',
            message: "You have been assigned '{$task->title}'",
            context: ['assigned_by' => $event->assigner->name],
            actor: $event->assigner,
        );
    }

    public function handleTaskOverdue(TaskOverdue $event): void
    {
        $task = $event->task;

        // Notify assignee + all managers in the hotel
        $managers = User::query()
            ->where('hotel_id', $task->hotel_id)
            ->where('role', UserRole::Manager)
            ->get();

        $recipients = $managers->push($task->assignee)->unique('id');

        $this->notificationService->notify(
            hotel: $task->hotel,
            recipients: $recipients,
            eventType: EventType::TaskOverdue,
            entity: $task,
            title: 'Task Overdue',
            message: "Task '{$task->title}' is past its due date",
            context: ['due_at' => $task->due_at?->toIso8601String()],
        );
    }

    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $task = $event->task;

        $this->notificationService->notify(
            hotel: $task->hotel,
            recipients: [$task->creator],
            eventType: EventType::TaskCompleted,
            entity: $task,
            title: 'Task Completed',
            message: "Task '{$task->title}' has been marked as complete",
            actor: $event->completedBy,
        );
    }

    public function handleMeetingReminder(MeetingReminder $event): void
    {
        $meeting = $event->meeting;

        $this->notificationService->notify(
            hotel: $meeting->hotel,
            recipients: $meeting->participants,
            eventType: EventType::MeetingReminder,
            entity: $meeting,
            title: 'Meeting Reminder',
            message: "'{$meeting->title}' is starting soon",
            context: ['starts_at' => $meeting->starts_at->toIso8601String()],
        );
    }

    public function handleMeetingCancelled(MeetingCancelled $event): void
    {
        $meeting = $event->meeting;

        $this->notificationService->notify(
            hotel: $meeting->hotel,
            recipients: $meeting->participants,
            eventType: EventType::MeetingCancelled,
            entity: $meeting,
            title: 'Meeting Cancelled',
            message: "'{$meeting->title}' has been cancelled",
            actor: $event->cancelledBy,
        );
    }

    public function handleUserMentionedInChat(UserMentionedInChat $event): void
    {
        $chatMessage = $event->chatMessage;
        $mentionedIds = $chatMessage->mentioned_user_ids ?? [];

        if (empty($mentionedIds)) {
            return;
        }

        $mentionedUsers = User::query()->whereIn('id', $mentionedIds)->get();

        $this->notificationService->notify(
            hotel: $chatMessage->hotel,
            recipients: $mentionedUsers,
            eventType: EventType::ChatMentioned,
            entity: $chatMessage,
            title: 'You were mentioned',
            message: "{$chatMessage->sender->name} mentioned you in a chat",
            actor: $chatMessage->sender,
        );
    }

    public function handleVideoRoomStarted(VideoRoomStarted $event): void
    {
        $videoRoom = $event->videoRoom;

        $this->notificationService->notify(
            hotel: $videoRoom->hotel,
            recipients: collect($event->invitedParticipants),
            eventType: EventType::VideoStarted,
            entity: $videoRoom,
            title: 'Video Room Started',
            message: "'{$videoRoom->name}' is now live",
            actor: $videoRoom->starter,
        );
    }

    public function handleSystemAlertBroadcast(SystemAlertBroadcast $event): void
    {
        $recipients = User::query()
            ->where('hotel_id', $event->hotel->getKey())
            ->whereIn('role', $event->targetRoles)
            ->get();

        // System alerts need a "virtual" entity — we use the hotel itself
        $this->notificationService->notify(
            hotel: $event->hotel,
            recipients: $recipients,
            eventType: EventType::SystemAlert,
            entity: $event->hotel,
            title: $event->title,
            message: $event->message,
            deepLink: '/system/alerts',
            actor: $event->sender,
        );
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            TaskAssigned::class => 'handleTaskAssigned',
            TaskOverdue::class => 'handleTaskOverdue',
            TaskCompleted::class => 'handleTaskCompleted',
            MeetingReminder::class => 'handleMeetingReminder',
            MeetingCancelled::class => 'handleMeetingCancelled',
            UserMentionedInChat::class => 'handleUserMentionedInChat',
            VideoRoomStarted::class => 'handleVideoRoomStarted',
            SystemAlertBroadcast::class => 'handleSystemAlertBroadcast',
        ];
    }
}
