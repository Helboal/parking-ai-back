<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Obtener o crear tipos necesarios
        $subscriptionType = SubscriptionType::inRandomOrder()->first() ?? SubscriptionType::factory();
        $branch = Branch::inRandomOrder()->first() ?? Branch::factory();
        $vehicleType = VehicleType::inRandomOrder()->first() ?? VehicleType::factory();

        // Si es un modelo, obtener el ID
        $subscriptionTypeId = is_object($subscriptionType) ? $subscriptionType->id : $subscriptionType;
        $branchId = is_object($branch) ? $branch->id : $branch;
        $vehicleTypeId = is_object($vehicleType) ? $vehicleType->id : $vehicleType;

        // Obtener la duración según el tipo de suscripción
        $duration = is_object($subscriptionType)
            ? $subscriptionType->duration_days
            : SubscriptionType::find($subscriptionTypeId)->duration_days ?? 30;

        // Fecha de inicio aleatoria en los últimos 6 meses
        $startDate = fake()->dateTimeBetween('-6 months', 'now');

        // Fecha de fin: start_date + duración según subscription_type_id
        $endDate = (clone $startDate)->modify("+{$duration} days");

        // 80% activas, 20% inactivas
        $isActive = fake()->boolean(80);

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 50000, 500000),
            'is_active' => $isActive,
            'customer_id' => Customer::factory(),
            'branch_id' => $branchId,
            'vehicle_type_id' => $vehicleTypeId,
            'subscription_type_id' => $subscriptionTypeId,
        ];
    }

    /**
     * Indicate that the subscription is active with end_date in the future.
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            // Obtener el tipo de suscripción para calcular la duración
            $subscriptionType = SubscriptionType::find($attributes['subscription_type_id'])
                ?? SubscriptionType::inRandomOrder()->first()
                ?? SubscriptionType::factory()->create();

            $duration = $subscriptionType->duration_days;

            // Fecha de inicio en los últimos 2 meses
            $startDate = fake()->dateTimeBetween('-2 months', 'now');

            // Fecha de fin en el futuro (start_date + duración)
            $endDate = (clone $startDate)->modify("+{$duration} days");

            return [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the subscription is expired with end_date in the past.
     */
    public function expired(): Factory
    {
        return $this->state(function (array $attributes) {
            // Obtener el tipo de suscripción para calcular la duración
            $subscriptionType = SubscriptionType::find($attributes['subscription_type_id'])
                ?? SubscriptionType::inRandomOrder()->first()
                ?? SubscriptionType::factory()->create();

            $duration = $subscriptionType->duration_days;

            // Fecha de inicio en el pasado (hace más de la duración + 1 mes)
            $startDate = fake()->dateTimeBetween('-' . ($duration + 60) . ' days', '-' . ($duration + 30) . ' days');

            // Fecha de fin también en el pasado (start_date + duración)
            $endDate = (clone $startDate)->modify("+{$duration} days");

            return [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the subscription is monthly (30 days).
     */
    public function monthly(): Factory
    {
        return $this->state(function (array $attributes) {
            $monthlyType = SubscriptionType::firstOrCreate(
                ['code' => 'MONTHLY'],
                [
                    'name' => 'Mensual',
                    'duration_days' => 30,
                ]
            );

            // Fecha de inicio aleatoria en los últimos 6 meses
            $startDate = fake()->dateTimeBetween('-6 months', 'now');

            // Fecha de fin: start_date + 30 días
            $endDate = (clone $startDate)->modify('+30 days');

            return [
                'subscription_type_id' => $monthlyType->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];
        });
    }

    /**
     * Indicate that the subscription is quarterly (90 days).
     */
    public function quarterly(): Factory
    {
        return $this->state(function (array $attributes) {
            $quarterlyType = SubscriptionType::firstOrCreate(
                ['code' => 'QUARTERLY'],
                [
                    'name' => 'Trimestral',
                    'duration_days' => 90,
                ]
            );

            // Fecha de inicio aleatoria en los últimos 6 meses
            $startDate = fake()->dateTimeBetween('-6 months', 'now');

            // Fecha de fin: start_date + 90 días
            $endDate = (clone $startDate)->modify('+90 days');

            return [
                'subscription_type_id' => $quarterlyType->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];
        });
    }

    /**
     * Indicate that the subscription is annual (365 days).
     */
    public function annual(): Factory
    {
        return $this->state(function (array $attributes) {
            $annualType = SubscriptionType::firstOrCreate(
                ['code' => 'ANNUAL'],
                [
                    'name' => 'Anual',
                    'duration_days' => 365,
                ]
            );

            // Fecha de inicio aleatoria en los últimos 6 meses
            $startDate = fake()->dateTimeBetween('-6 months', 'now');

            // Fecha de fin: start_date + 365 días
            $endDate = (clone $startDate)->modify('+365 days');

            return [
                'subscription_type_id' => $annualType->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];
        });
    }

    /**
     * Indicate that the subscription is inactive.
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
