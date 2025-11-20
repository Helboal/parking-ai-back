<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            ['name' => 'Efectivo', 'code' => 'CASH', 'is_active' => true],
            ['name' => 'Tarjeta de Crédito', 'code' => 'CREDIT_CARD', 'is_active' => true],
            ['name' => 'Tarjeta de Débito', 'code' => 'DEBIT_CARD', 'is_active' => true],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::firstOrCreate(
                ['code' => $paymentMethod['code']],
                [
                    'name' => $paymentMethod['name'],
                    'is_active' => $paymentMethod['is_active'],
                ]
            );
        }
    }
}
