<?php

namespace Database\Factories;

use App\Enums\EntityType;
use App\Enums\EventType;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hotel = Hotel::factory();

        return [
            'hotel_id' => $hotel,
            'user_id' => User::factory()->state(fn () => ['hotel_id' => $hotel]),
            'event_type' => EventType::TaskAssigned,
            'entity_type' => EntityType::Task,
            'entity_id' => Str::uuid()->toString(),
            'deep_link' => '/agenda/tasks/' . Str::uuid()->toString(),
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(),
            'context_json' => null,
            'is_read' => false,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
