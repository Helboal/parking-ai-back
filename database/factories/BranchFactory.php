<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'total_spaces' => fake()->numberBetween(50, 200),
            'available_spaces' => fake()->numberBetween(10, 50),
            'is_active' => true,
            'user_id' => null,
        ];
    }
}
