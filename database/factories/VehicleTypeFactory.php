<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehicleType>
 */
class VehicleTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Car', 'Motorcycle', 'Truck', 'Van', 'SUV', 'Bus'];
        $type = fake()->unique()->randomElement($types);

        return [
            'name' => $type,
            'code' => strtoupper(substr($type, 0, 3)),
        ];
    }
}
