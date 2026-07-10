<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Home', 'Office', 'Work']),
            'street' => fake()->streetName(),
            'details' => 'Damascus, '.fake()->streetName().', Building '.fake()->numberBetween(1, 60),
            'latitude' => fake()->randomFloat(7, 33.45, 33.58),
            'longitude' => fake()->randomFloat(7, 36.23, 36.40),
        ];
    }
}
