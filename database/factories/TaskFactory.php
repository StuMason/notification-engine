<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Hotel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hotel = Hotel::factory();

        return [
            'hotel_id' => $hotel,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'assigned_to' => User::factory()->state(fn () => ['hotel_id' => $hotel]),
            'created_by' => User::factory()->state(fn () => ['hotel_id' => $hotel]),
            'due_at' => fake()->dateTimeBetween('now', '+7 days'),
            'status' => TaskStatus::Pending,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_at' => fake()->dateTimeBetween('-3 days', '-1 hour'),
            'status' => TaskStatus::Overdue,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Completed,
        ]);
    }
}
