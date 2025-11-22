<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // rate_per_minute: precio aleatorio 50-300 pesos
        $ratePerMinute = fake()->randomFloat(2, 50, 300);

        // discount_percentage: 0 (70%), 5-20% aleatorio (30%)
        $hasDiscount = fake()->boolean(30);
        $discountPercentage = $hasDiscount ? fake()->numberBetween(5, 20) : 0;

        // flat_rate_applied: false (80%), true (20%)
        $flatRateApplied = fake()->boolean(20);

        // subtotal: monto aleatorio 5000-50000
        $subtotal = fake()->randomFloat(2, 5000, 50000);

        // discount_amount: calculado como (subtotal * discount_percentage / 100)
        $discountAmount = ($subtotal * $discountPercentage) / 100;

        // tax_amount: calculado como (subtotal - discount_amount) * 0.19 (IVA 19%)
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $taxableAmount * 0.19;

        // total: subtotal - discount_amount + tax_amount
        $total = $subtotal - $discountAmount + $taxAmount;

        // entry_id: Entry::factory()
        $entry = Entry::inRandomOrder()->first();
        if (!$entry) {
            $entry = Entry::factory()->create();
        }

        return [
            'rate_per_minute' => $ratePerMinute,
            'discount_percentage' => $discountPercentage,
            'flat_rate_applied' => $flatRateApplied,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'entry_id' => $entry->id,
        ];
    }

    /**
     * Indicate that the invoice has a discount applied.
     */
    public function withDiscount(): Factory
    {
        return $this->state(function (array $attributes) {
            // discount_percentage = 10, recalcula discount_amount
            $discountPercentage = 10;
            $subtotal = $attributes['subtotal'];
            $discountAmount = ($subtotal * $discountPercentage) / 100;

            // Recalcular tax_amount y total
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = $taxableAmount * 0.19;
            $total = $subtotal - $discountAmount + $taxAmount;

            return [
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ];
        });
    }

    /**
     * Indicate that the invoice has a flat rate applied.
     */
    public function withFlatRate(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'flat_rate_applied' => true,
            ];
        });
    }

    /**
     * Indicate that the invoice has no tax.
     */
    public function noTax(): Factory
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $discountAmount = $attributes['discount_amount'];
            $total = $subtotal - $discountAmount;

            return [
                'tax_amount' => 0,
                'total' => $total,
            ];
        });
    }
}
