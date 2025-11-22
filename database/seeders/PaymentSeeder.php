<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 20 pagos para facturas (invoices)
        Payment::factory()
            ->count(20)
            ->forInvoice()
            ->create();

        // Crear 10 pagos para suscripciones
        Payment::factory()
            ->count(10)
            ->forSubscription()
            ->create();

        // Nota: Los pagos se crean con una mezcla de:
        // - Estados: 80% completed, 10% pending, 5% failed, 5% refunded
        // - Métodos de pago: mayormente efectivo (por defecto en factory)
        // - Algunos con tarjeta y número de referencia
    }
}
