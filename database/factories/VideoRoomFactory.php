<?php

namespace Database\Factories;

use App\Enums\VideoRoomStatus;
use App\Models\Hotel;
use App\Models\User;
use App\Models\VideoRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoRoom>
 */
class VideoRoomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hotel = Hotel::factory();

        return [
            'hotel_id' => $hotel,
            'name' => fake()->words(3, true) . ' Room',
            'started_by' => User::factory()->state(fn () => ['hotel_id' => $hotel]),
            'status' => VideoRoomStatus::Active,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn () => ['status' => VideoRoomStatus::Ended]);
    }
}
