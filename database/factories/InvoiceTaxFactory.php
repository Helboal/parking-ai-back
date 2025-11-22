<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceTax;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceTax>
 */
class InvoiceTaxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = InvoiceTax::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // invoice_id: Invoice::factory()
        $invoice = Invoice::inRandomOrder()->first() ?? Invoice::factory()->create();
        $invoiceId = is_object($invoice) ? $invoice->id : $invoice;

        // tax_id: Tax::inRandomOrder()->first()->id ?? Tax::factory()
        $tax = Tax::where('is_active', true)->inRandomOrder()->first();

        if (!$tax) {
            // Si no hay impuestos, crear uno por defecto (IVA 19%)
            $tax = Tax::create([
                'name' => 'IVA',
                'code' => 'IVA',
                'percentage' => 19.00,
                'is_active' => true,
            ]);
        }

        $taxId = $tax->id;

        // tax_percentage: 19.00 (IVA por defecto)
        $taxPercentage = $tax->percentage;

        // tax_amount: calculado según el subtotal de la factura
        // Obtener la factura para calcular el tax_amount
        if (is_object($invoice)) {
            $subtotal = $invoice->subtotal;
            $discountAmount = $invoice->discount_amount;
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = ($taxableAmount * $taxPercentage) / 100;
        } else {
            // Si no podemos obtener la factura, usar un valor aleatorio
            $taxableAmount = fake()->randomFloat(2, 5000, 50000);
            $taxAmount = ($taxableAmount * $taxPercentage) / 100;
        }

        return [
            'invoice_id' => $invoiceId,
            'tax_id' => $taxId,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => $taxAmount,
        ];
    }
}
