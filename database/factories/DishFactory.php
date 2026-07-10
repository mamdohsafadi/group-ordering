<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => 'صنف '.fake()->numberBetween(1, 999),
            'eng_name' => ucfirst(fake()->words(2, true)),
            'price' => fake()->randomFloat(2, 5, 80),
            'active' => 1,
        ];
    }
}
