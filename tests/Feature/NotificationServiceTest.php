<?php

use App\Enums\EntityType;
use App\Enums\EventType;
use App\Events\NotificationCreated;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->hotel = Hotel::factory()->create();
    $this->service = app(NotificationService::class);
});

it('creates a notification with all required fields', function () {
    $user = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $count = $this->service->notify(
        hotel: $this->hotel,
        recipients: collect([$user]),
        eventType: EventType::TaskAssigned,
        entity: $task,
        title: 'Test Notification',
        message: 'You have a new task',
    );

    expect($count)->toBe(1);

    $notification = Notification::first();
    expect($notification)
        ->hotel_id->toBe($this->hotel->id)
        ->user_id->toBe($user->id)
        ->event_type->toBe(EventType::TaskAssigned)
        ->entity_type->toBe(EntityType::Task)
        ->entity_id->toBe($task->id)
        ->title->toBe('Test Notification')
        ->message->toBe('You have a new task')
        ->is_read->toBeFalse()
        ->read_at->toBeNull()
        ->deep_link->toContain('/agenda/tasks/');
});

it('excludes actor from recipients (no-self-notification)', function () {
    $actor = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $other = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $other->id,
        'created_by' => $actor->id,
    ]);

    $count = $this->service->notify(
        hotel: $this->hotel,
        recipients: collect([$actor, $other]),
        eventType: EventType::TaskAssigned,
        entity: $task,
        title: 'Task Assigned',
        message: 'You have a task',
        actor: $actor,
    );

    expect($count)->toBe(1);
    expect(Notification::where('user_id', $actor->id)->exists())->toBeFalse();
    expect(Notification::where('user_id', $other->id)->exists())->toBeTrue();
});

it('deduplicates same event processed twice', function () {
    $user = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $params = [
        'hotel' => $this->hotel,
        'recipients' => collect([$user]),
        'eventType' => EventType::TaskAssigned,
        'entity' => $task,
        'title' => 'Task Assigned',
        'message' => 'You have a task',
    ];

    $first = $this->service->notify(...$params);
    $second = $this->service->notify(...$params);

    expect($first)->toBe(1);
    expect($second)->toBe(0);
    expect(Notification::count())->toBe(1);
});

it('fans out to individual per-recipient records', function () {
    $users = User::factory()->count(5)->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $users->first()->id,
        'created_by' => $users->first()->id,
    ]);

    $count = $this->service->notify(
        hotel: $this->hotel,
        recipients: $users,
        eventType: EventType::TaskAssigned,
        entity: $task,
        title: 'Task Assigned',
        message: 'You have a task',
    );

    expect($count)->toBe(5);
    expect(Notification::count())->toBe(5);

    foreach ($users as $user) {
        expect(Notification::where('user_id', $user->id)->exists())->toBeTrue();
    }
});

it('replaying an event does not create duplicate notifications', function () {
    $user = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $params = [
        'hotel' => $this->hotel,
        'recipients' => collect([$user]),
        'eventType' => EventType::TaskAssigned,
        'entity' => $task,
        'title' => 'Task Assigned',
        'message' => 'You have a task',
    ];

    // Simulate replay: call three times
    $this->service->notify(...$params);
    $this->service->notify(...$params);
    $this->service->notify(...$params);

    expect(Notification::count())->toBe(1);
});

it('scopes notifications to hotel_id (tenant isolation)', function () {
    $hotel2 = Hotel::factory()->create();
    $user1 = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $user2 = User::factory()->create(['hotel_id' => $hotel2->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $user1->id,
        'created_by' => $user1->id,
    ]);

    $this->service->notify(
        hotel: $this->hotel,
        recipients: collect([$user1]),
        eventType: EventType::TaskAssigned,
        entity: $task,
        title: 'Task',
        message: 'Task message',
    );

    expect(Notification::where('hotel_id', $this->hotel->id)->count())->toBe(1);
    expect(Notification::where('hotel_id', $hotel2->id)->count())->toBe(0);
});

it('dispatches NotificationCreated event after creation', function () {
    Event::fake([NotificationCreated::class]);

    $user = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $this->service->notify(
        hotel: $this->hotel,
        recipients: collect([$user]),
        eventType: EventType::TaskAssigned,
        entity: $task,
        title: 'Test',
        message: 'Test',
    );

    Event::assertDispatched(NotificationCreated::class, 1);
});

it('resolves string user IDs to User models', function () {
    $user = User::factory()->create(['hotel_id' => $this->hotel->id]);
    $task = Task::factory()->create([
        'hotel_id' => $this->hotel->id,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $count = $this->service->notify(
        hotel: $this->hotel,
        recipients: [$user->id],
        eventType: EventType::TaskAssigned,
        entity: $task,
        title: 'Test',
        message: 'Test',
    );

    expect($count)->toBe(1);
});
