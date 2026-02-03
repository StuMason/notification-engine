<?php

use App\Enums\EventType;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\User;

beforeEach(function () {
    $this->hotel = Hotel::factory()->create();
    $this->user = User::factory()->create(['hotel_id' => $this->hotel->id]);
});

it('returns only notifications for the authenticated user\'s hotel', function () {
    $otherHotel = Hotel::factory()->create();
    $otherUser = User::factory()->create(['hotel_id' => $otherHotel->id]);

    Notification::factory()->count(3)->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
    ]);
    Notification::factory()->count(2)->create([
        'hotel_id' => $otherHotel->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notifications');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('returns notifications newest first', function () {
    Notification::factory()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'title' => 'Older',
        'created_at' => now()->subHour(),
    ]);
    Notification::factory()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'title' => 'Newer',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notifications');

    $data = $response->json('data');
    expect($data[0]['title'])->toBe('Newer');
    expect($data[1]['title'])->toBe('Older');
});

it('filters by is_read', function () {
    Notification::factory()->count(3)->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'is_read' => false,
    ]);
    Notification::factory()->count(2)->read()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
    ]);

    $unread = $this->actingAs($this->user)
        ->getJson('/api/notifications?is_read=0');
    expect($unread->json('data'))->toHaveCount(3);

    $read = $this->actingAs($this->user)
        ->getJson('/api/notifications?is_read=1');
    expect($read->json('data'))->toHaveCount(2);
});

it('filters by event_type', function () {
    Notification::factory()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'event_type' => EventType::TaskAssigned,
    ]);
    Notification::factory()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'event_type' => EventType::ChatMentioned,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notifications?event_type=task.assigned');

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.event_type'))->toBe('task.assigned');
});

it('returns accurate unread count', function () {
    Notification::factory()->count(5)->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'is_read' => false,
    ]);
    Notification::factory()->count(3)->read()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notifications/unread-count');

    $response->assertOk();
    expect($response->json('count'))->toBe(5);
});

it('marks a single notification as read', function () {
    $notification = Notification::factory()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'is_read' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/notifications/{$notification->id}/read");

    $response->assertOk();

    $notification->refresh();
    expect($notification->is_read)->toBeTrue();
    expect($notification->read_at)->not->toBeNull();
});

it('marks all unread notifications as read for current user only', function () {
    $otherUser = User::factory()->create(['hotel_id' => $this->hotel->id]);

    Notification::factory()->count(3)->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'is_read' => false,
    ]);
    Notification::factory()->count(2)->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $otherUser->id,
        'is_read' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson('/api/notifications/read-all');

    $response->assertOk();
    expect($response->json('count'))->toBe(3);

    // Other user's notifications untouched
    expect(Notification::where('user_id', $otherUser->id)->where('is_read', false)->count())->toBe(2);
});

it('prevents reading notification from another hotel', function () {
    $otherHotel = Hotel::factory()->create();
    $otherUser = User::factory()->create(['hotel_id' => $otherHotel->id]);
    $notification = Notification::factory()->create([
        'hotel_id' => $otherHotel->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/notifications/{$notification->id}/read");

    $response->assertForbidden();
});

it('includes unread_count in list response meta', function () {
    Notification::factory()->count(3)->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
        'is_read' => false,
    ]);
    Notification::factory()->count(2)->read()->create([
        'hotel_id' => $this->hotel->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notifications');

    expect($response->json('meta.unread_count'))->toBe(3);
});

it('requires authentication', function () {
    $this->getJson('/api/notifications')->assertUnauthorized();
    $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
});
