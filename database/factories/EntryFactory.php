<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entry>
 */
class EntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Entry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // entry_datetime: fecha aleatoria en los últimos 30 días
        $entryDatetime = fake()->dateTimeBetween('-30 days', 'now');

        // exit_datetime: null (50%), o entry_datetime + tiempo aleatorio 30-480 minutos (50%)
        $hasExit = fake()->boolean(50);
        $exitDatetime = null;
        $totalMinutes = null;
        $status = 'active';

        if ($hasExit) {
            // Tiempo aleatorio entre 30 y 480 minutos (30 min - 8 horas)
            $randomMinutes = fake()->numberBetween(30, 480);
            $exitDatetime = (clone $entryDatetime)->modify("+{$randomMinutes} minutes");
            $totalMinutes = $randomMinutes;
            $status = 'completed';
        }

        // Obtener o crear entidades relacionadas
        $entryUser = User::inRandomOrder()->first() ?? User::factory()->create();
        $branch = Branch::inRandomOrder()->first() ?? Branch::factory()->create();
        $vehicle = Vehicle::inRandomOrder()->first() ?? Vehicle::factory()->create();
        $entryType = EntryType::firstOrCreate(
            ['code' => 'REGULAR'],
            ['name' => 'Regular']
        );

        // subscription_id: null (80%), subscription aleatoria (20%)
        $hasSubscription = fake()->boolean(20);
        $subscriptionId = null;

        if ($hasSubscription) {
            // Intentar obtener una suscripción activa
            $subscription = Subscription::where('is_active', true)
                ->inRandomOrder()
                ->first();

            $subscriptionId = $subscription ? $subscription->id : null;
        }

        // exit_user_id: null si exit_datetime es null, sino User::inRandomOrder()->first()->id
        $exitUserId = null;
        if ($exitDatetime) {
            $exitUser = User::inRandomOrder()->first() ?? User::factory();
            $exitUserId = is_object($exitUser) ? $exitUser->id : $exitUser;
        }

        // notes: null (70%), texto aleatorio (30%)
        $hasNotes = fake()->boolean(30);
        $notes = $hasNotes ? fake()->sentence(10) : null;

        return [
            'entry_datetime' => $entryDatetime,
            'exit_datetime' => $exitDatetime,
            'total_minutes' => $totalMinutes,
            'status' => $status,
            'entry_user_id' => is_object($entryUser) ? $entryUser->id : $entryUser,
            'exit_user_id' => $exitUserId,
            'branch_id' => is_object($branch) ? $branch->id : $branch,
            'vehicle_id' => is_object($vehicle) ? $vehicle->id : $vehicle,
            'entry_type_id' => is_object($entryType) ? $entryType->id : $entryType,
            'subscription_id' => $subscriptionId,
            'notes' => $notes,
        ];
    }

    /**
     * Indicate that the entry is active (no exit).
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'exit_datetime' => null,
                'total_minutes' => null,
                'status' => 'active',
                'exit_user_id' => null,
            ];
        });
    }

    /**
     * Indicate that the entry is completed (has exit).
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            $entryDatetime = $attributes['entry_datetime'] instanceof \DateTime
                ? $attributes['entry_datetime']
                : new \DateTime($attributes['entry_datetime']);

            // Tiempo aleatorio entre 30 y 480 minutos (30 min - 8 horas)
            $randomMinutes = fake()->numberBetween(30, 480);
            $exitDatetime = (clone $entryDatetime)->modify("+{$randomMinutes} minutes");

            // Obtener un usuario para la salida
            $exitUser = User::inRandomOrder()->first() ?? User::factory();
            $exitUserId = is_object($exitUser) ? $exitUser->id : $exitUser;

            return [
                'exit_datetime' => $exitDatetime,
                'total_minutes' => $randomMinutes,
                'status' => 'completed',
                'exit_user_id' => $exitUserId,
            ];
        });
    }

    /**
     * Indicate that the entry is cancelled.
     */
    public function cancelled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'exit_datetime' => null,
                'total_minutes' => null,
                'exit_user_id' => null,
            ];
        });
    }

    /**
     * Indicate that the entry has a subscription.
     */
    public function withSubscription(): Factory
    {
        return $this->state(function (array $attributes) {
            // Intentar obtener una suscripción activa
            $subscription = Subscription::where('is_active', true)
                ->inRandomOrder()
                ->first();

            // Si no hay suscripciones activas, crear una
            if (! $subscription) {
                $subscription = Subscription::factory()->active()->create();
            }

            return [
                'subscription_id' => $subscription->id,
            ];
        });
    }
}
