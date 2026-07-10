<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Kitchen',
            'arabic_name' => 'مطعم '.fake()->numberBetween(1, 999),
            'tagline' => fake()->catchPhrase(),
            'active' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => 0]);
    }
}
