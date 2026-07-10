<?php

namespace Database\Factories;

use App\Models\GroupOrder;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\GroupOrderService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupOrder>
 */
class GroupOrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'leader_id' => User::factory(),
            'restaurant_id' => Restaurant::factory(),
            'delivery_address_id' => null,
            'status' => GroupOrder::STATUS_ACTIVE,
            'shareable_link' => bin2hex(random_bytes(16)),
            'delivery_time_type' => GroupOrder::DELIVERY_ASAP,
            'scheduled_time' => null,
            'expires_at' => now()->addMinutes(GroupOrderService::JOIN_WINDOW_MINUTES),
            'submitted_at' => null,
        ];
    }

    /** Window elapsed (status still ACTIVE — expiry is derived or swept). */
    public function windowElapsed(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => GroupOrder::STATUS_CANCELLED]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => GroupOrder::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }
}
