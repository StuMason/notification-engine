<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hotel = Hotel::factory();

        return [
            'hotel_id' => $hotel,
            'title' => fake()->sentence(3),
            'starts_at' => fake()->dateTimeBetween('+1 hour', '+7 days'),
            'created_by' => User::factory()->state(fn () => ['hotel_id' => $hotel]),
        ];
    }
}
