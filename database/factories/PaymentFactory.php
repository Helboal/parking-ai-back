<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Determinar si es para factura o suscripción (50/50)
        $isForInvoice = $this->faker->boolean(50);

        // Obtener método de pago (default a efectivo)
        $paymentMethod = PaymentMethod::firstOrCreate(
            ['code' => 'CASH'],
            ['name' => 'Efectivo', 'is_active' => true]
        );

        // Determinar si tiene referencia (no para efectivo, sí para tarjetas)
        $referenceNumber = $paymentMethod->code === 'CASH' ? null : 'TRX-'.strtoupper($this->faker->bothify('??##??##'));

        // Determinar estado con probabilidades
        $statusRandom = $this->faker->numberBetween(1, 100);
        if ($statusRandom <= 80) {
            $status = 'completed';
        } elseif ($statusRandom <= 90) {
            $status = 'pending';
        } elseif ($statusRandom <= 95) {
            $status = 'failed';
        } else {
            $status = 'refunded';
        }

        return [
            'amount' => $this->faker->randomFloat(2, 5000, 500000),
            'payment_datetime' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'status' => $status,
            'reference_number' => $referenceNumber,
            'invoice_id' => $isForInvoice
                ? (Invoice::inRandomOrder()->first()?->id ?? Invoice::factory()->create()->id)
                : null,
            'subscription_id' => ! $isForInvoice
                ? (Subscription::inRandomOrder()->first()?->id ?? null)
                : null,
            'payment_method_id' => $paymentMethod->id,
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
        ];
    }

    /**
     * Indicate that the payment is for an invoice.
     */
    public function forInvoice(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'invoice_id' => Invoice::inRandomOrder()->first()?->id ?? Invoice::factory()->create()->id,
                'subscription_id' => null,
            ];
        });
    }

    /**
     * Indicate that the payment is for a subscription.
     */
    public function forSubscription(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'invoice_id' => null,
                'subscription_id' => Subscription::inRandomOrder()->first()?->id ?? Subscription::factory()->create()->id,
            ];
        });
    }

    /**
     * Indicate that the payment is with card (has reference number).
     */
    public function withCard(): static
    {
        return $this->state(function (array $attributes) {
            // Buscar o crear método de pago con tarjeta
            $paymentMethod = PaymentMethod::firstOrCreate(
                ['code' => 'CREDIT_CARD'],
                ['name' => 'Tarjeta de Crédito', 'is_active' => true]
            );

            return [
                'payment_method_id' => $paymentMethod->id,
                'reference_number' => 'TRX-'.strtoupper($this->faker->bothify('??##??##')),
            ];
        });
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
            ];
        });
    }

    /**
     * Indicate that the payment was refunded.
     */
    public function refunded(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'refunded',
                'notes' => 'Pago reembolsado: '.$this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the payment is completed (default state).
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }
}
