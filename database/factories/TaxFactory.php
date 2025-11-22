<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Tipos de impuestos comunes en Colombia
        $taxes = [
            ['name' => 'IVA', 'code' => 'IVA', 'percentage' => 19.00],
            ['name' => 'IVA Reducido', 'code' => 'IVA_5', 'percentage' => 5.00],
            ['name' => 'INC', 'code' => 'INC', 'percentage' => 8.00],
            ['name' => 'ICA', 'code' => 'ICA', 'percentage' => 1.16],
        ];

        $tax = fake()->randomElement($taxes);

        return [
            'name' => $tax['name'],
            'code' => $tax['code'].'_'.fake()->unique()->numberBetween(1000, 9999),
            'percentage' => $tax['percentage'],
            'is_active' => fake()->boolean(90), // 90% activos
        ];
    }

    /**
     * Indicate that the tax is IVA 19%.
     */
    public function iva(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'IVA',
                'code' => 'IVA',
                'percentage' => 19.00,
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the tax is active.
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the tax is inactive.
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
