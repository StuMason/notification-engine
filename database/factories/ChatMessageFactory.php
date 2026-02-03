<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hotel = Hotel::factory();

        return [
            'hotel_id' => $hotel,
            'room_id' => Str::uuid()->toString(),
            'sender_id' => User::factory()->state(fn () => ['hotel_id' => $hotel]),
            'body' => fake()->sentence(),
            'mentioned_user_ids' => null,
        ];
    }

    /**
     * @param  array<string>  $userIds
     */
    public function mentioning(array $userIds): static
    {
        return $this->state(fn () => ['mentioned_user_ids' => $userIds]);
    }
}
