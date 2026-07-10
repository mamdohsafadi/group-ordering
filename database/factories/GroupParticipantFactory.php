<?php

namespace Database\Factories;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupParticipant>
 */
class GroupParticipantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'group_order_id' => GroupOrder::factory(),
            'user_id' => User::factory(),
            'status' => GroupParticipant::STATUS_JOINED,
            'joined_at' => now(),
            'left_at' => null,
        ];
    }

    public function left(): static
    {
        return $this->state(fn () => [
            'status' => GroupParticipant::STATUS_LEFT,
            'left_at' => now(),
        ]);
    }
}
