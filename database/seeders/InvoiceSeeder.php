<?php

namespace Database\Seeders;

use App\Models\Entry;
use App\Models\Invoice;
use App\Models\InvoiceTax;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener entradas completadas existentes (que tengan exit_datetime)
        $completedEntries = Entry::whereNotNull('exit_datetime')
            ->where('status', 'completed')
            ->get();

        // Si no hay suficientes entradas completadas, mostrar advertencia
        if ($completedEntries->count() < 20) {
            $this->command->warn("Solo hay {$completedEntries->count()} entradas completadas. Se crearán facturas para las disponibles.");
        }

        // Tomar hasta 20 entradas
        $entriesToInvoice = $completedEntries->take(20);

        // Contador para cada tipo de factura
        $invoicesCreated = 0;
        $noDiscountCount = 0;
        $withDiscountCount = 0;
        $withFlatRateCount = 0;

        foreach ($entriesToInvoice as $entry) {
            $invoice = null;

            // Mezclar:
            // - 10 sin descuento (50%)
            // - 5 con descuento (25%)
            // - 5 con tarifa plana aplicada (25%)

            if ($noDiscountCount < 10) {
                // Factura sin descuento
                $invoice = Invoice::factory()->create([
                    'entry_id' => $entry->id,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'flat_rate_applied' => false,
                ]);
                $noDiscountCount++;
            } elseif ($withDiscountCount < 5) {
                // Factura con descuento
                $invoice = Invoice::factory()->withDiscount()->create([
                    'entry_id' => $entry->id,
                    'flat_rate_applied' => false,
                ]);
                $withDiscountCount++;
            } elseif ($withFlatRateCount < 5) {
                // Factura con tarifa plana aplicada
                $invoice = Invoice::factory()->withFlatRate()->create([
                    'entry_id' => $entry->id,
                ]);
                $withFlatRateCount++;
            } else {
                // Si ya se completaron los 20, romper el ciclo
                break;
            }

            // Para cada factura, crear 1-2 invoice_taxes asociados
            if ($invoice) {
                $numberOfTaxes = fake()->numberBetween(1, 2);

                // Obtener impuestos activos disponibles
                $availableTaxes = Tax::where('is_active', true)->get();

                if ($availableTaxes->isEmpty()) {
                    // Si no hay impuestos, crear uno por defecto (IVA 19%)
                    $defaultTax = Tax::firstOrCreate(
                        ['code' => 'IVA'],
                        [
                            'name' => 'IVA',
                            'percentage' => 19.00,
                            'is_active' => true,
                        ]
                    );
                    $availableTaxes = collect([$defaultTax]);
                }

                // Seleccionar impuestos aleatorios
                $selectedTaxes = $availableTaxes->random(min($numberOfTaxes, $availableTaxes->count()));

                foreach ($selectedTaxes as $tax) {
                    // Calcular el tax_amount basado en la factura
                    $taxableAmount = $invoice->subtotal - $invoice->discount_amount;
                    $taxAmount = ($taxableAmount * $tax->percentage) / 100;

                    InvoiceTax::create([
                        'invoice_id' => $invoice->id,
                        'tax_id' => $tax->id,
                        'tax_percentage' => $tax->percentage,
                        'tax_amount' => $taxAmount,
                    ]);
                }

                $invoicesCreated++;
            }
        }

        $this->command->info("Se crearon {$invoicesCreated} facturas:");
        $this->command->info("  - {$noDiscountCount} sin descuento");
        $this->command->info("  - {$withDiscountCount} con descuento");
        $this->command->info("  - {$withFlatRateCount} con tarifa plana aplicada");
    }
}
