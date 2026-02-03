<?php

use App\Enums\EventType;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Events\MeetingCancelled;
use App\Events\MeetingReminder;
use App\Events\SystemAlertBroadcast;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskOverdue;
use App\Events\UserMentionedInChat;
use App\Events\VideoRoomStarted;
use App\Models\ChatMessage;
use App\Models\Hotel;
use App\Models\Meeting;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Models\VideoRoom;

beforeEach(function () {
    $this->hotel = Hotel::factory()->create();
});

it('TaskAssigned creates notification for assignee', function () {
    $assigner = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $assignee = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $assignee->id,
        'created_by' => $assigner->id,
    ]);

    TaskAssigned::dispatch($task, $assigner);

    expect(Notification::where('user_id', $assignee->id)->where('event_type', EventType::TaskAssigned)->exists())->toBeTrue();
});

it('TaskAssigned does NOT notify the assigner (no-self rule)', function () {
    $assigner = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $assigner->id,
        'created_by' => $assigner->id,
    ]);

    TaskAssigned::dispatch($task, $assigner);

    expect(Notification::where('user_id', $assigner->id)->exists())->toBeFalse();
});

it('TaskOverdue notifies assignee AND managers', function () {
    $manager = User::factory()->manager()->create(['hotel_id' => $this->hotel->id]);
    $assignee = User::factory()->staff()->create(['hotel_id' => $this->hotel->id]);
    $creator = User::factory()->staff()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $assignee->id,
        'created_by' => $creator->id,
        'status' => TaskStatus::Overdue,
    ]);

    TaskOverdue::dispatch($task);

    expect(Notification::where('user_id', $assignee->id)->where('event_type', EventType::TaskOverdue)->exists())->toBeTrue();
    expect(Notification::where('user_id', $manager->id)->where('event_type', EventType::TaskOverdue)->exists())->toBeTrue();
});

it('TaskCompleted notifies the task creator', function () {
    $creator = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $assignee = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $assignee->id,
        'created_by' => $creator->id,
    ]);

    TaskCompleted::dispatch($task, $assignee);

    expect(Notification::where('user_id', $creator->id)->where('event_type', EventType::TaskCompleted)->exists())->toBeTrue();
    // Assignee (the completer) should not be notified
    expect(Notification::where('user_id', $assignee->id)->where('event_type', EventType::TaskCompleted)->exists())->toBeFalse();
});

it('MeetingReminder notifies all participants', function () {
    $creator = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $participant1 = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $participant2 = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $meeting = Meeting::factory()->create([
        'hotel_id' => $this->hotel->id,
        'created_by' => $creator->id,
    ]);
    $meeting->participants()->attach([$creator->id, $participant1->id, $participant2->id]);

    MeetingReminder::dispatch($meeting);

    expect(Notification::where('event_type', EventType::MeetingReminder)->count())->toBe(3);
});

it('MeetingCancelled notifies participants except canceller', function () {
    $canceller = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $participant = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $meeting = Meeting::factory()->create([
        'hotel_id' => $this->hotel->id,
        'created_by' => $canceller->id,
    ]);
    $meeting->participants()->attach([$canceller->id, $participant->id]);

    MeetingCancelled::dispatch($meeting, $canceller);

    expect(Notification::where('user_id', $participant->id)->exists())->toBeTrue();
    expect(Notification::where('user_id', $canceller->id)->exists())->toBeFalse();
});

it('UserMentionedInChat notifies mentioned user', function () {
    $sender = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $mentioned = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $chatMessage = ChatMessage::factory()->create([
        'hotel_id' => $this->hotel->id,
        'sender_id' => $sender->id,
        'mentioned_user_ids' => [$mentioned->id],
    ]);

    UserMentionedInChat::dispatch($chatMessage);

    expect(Notification::where('user_id', $mentioned->id)->where('event_type', EventType::ChatMentioned)->exists())->toBeTrue();
    // Sender should not be notified
    expect(Notification::where('user_id', $sender->id)->exists())->toBeFalse();
});

it('VideoRoomStarted notifies invited participants except starter', function () {
    $starter = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $invited = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $videoRoom = VideoRoom::factory()->create([
        'hotel_id' => $this->hotel->id,
        'started_by' => $starter->id,
    ]);

    VideoRoomStarted::dispatch($videoRoom, collect([$starter, $invited]));

    expect(Notification::where('user_id', $invited->id)->where('event_type', EventType::VideoStarted)->exists())->toBeTrue();
    expect(Notification::where('user_id', $starter->id)->exists())->toBeFalse();
});

it('SystemAlertBroadcast notifies all users with target roles in hotel only', function () {
    $admin = User::factory()->admin()->create(['hotel_id' => $this->hotel->id]);
    $staff = User::factory()->staff()->create(['hotel_id' => $this->hotel->id]);

    $otherHotel = Hotel::factory()->create();
    $otherAdmin = User::factory()->admin()->create(['hotel_id' => $otherHotel->id]);

    SystemAlertBroadcast::dispatch(
        $this->hotel,
        'Maintenance',
        'System going down',
        [UserRole::Admin],
    );

    expect(Notification::where('user_id', $admin->id)->where('event_type', EventType::SystemAlert)->exists())->toBeTrue();
    expect(Notification::where('user_id', $staff->id)->exists())->toBeFalse();
    expect(Notification::where('user_id', $otherAdmin->id)->exists())->toBeFalse();
});
