<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurar que existen los registros necesarios
        $customers = Customer::all();
        $branches = Branch::all();
        $vehicleTypes = VehicleType::all();
        $subscriptionTypes = SubscriptionType::all();

        // Si no hay datos, usar factory para crearlos
        if ($customers->isEmpty()) {
            $customers = Customer::factory()->count(10)->create();
        }

        if ($branches->isEmpty()) {
            $branches = Branch::factory()->count(3)->create();
        }

        if ($vehicleTypes->isEmpty()) {
            $vehicleTypes = collect([
                VehicleType::firstOrCreate(['code' => 'CAR'], ['name' => 'Carro']),
                VehicleType::firstOrCreate(['code' => 'MOTO'], ['name' => 'Motocicleta']),
            ]);
        }

        if ($subscriptionTypes->isEmpty()) {
            $subscriptionTypes = collect([
                SubscriptionType::firstOrCreate(['code' => 'MONTHLY'], ['name' => 'Mensual', 'duration_days' => 30]),
                SubscriptionType::firstOrCreate(['code' => 'QUARTERLY'], ['name' => 'Trimestral', 'duration_days' => 90]),
                SubscriptionType::firstOrCreate(['code' => 'ANNUAL'], ['name' => 'Anual', 'duration_days' => 365]),
            ]);
        }

        // Crear 10 suscripciones activas
        foreach (range(1, 10) as $index) {
            Subscription::factory()
                ->active()
                ->create([
                    'customer_id' => $customers->random()->id,
                    'branch_id' => $branches->random()->id,
                    'vehicle_type_id' => $vehicleTypes->random()->id,
                    'subscription_type_id' => $subscriptionTypes->random()->id,
                ]);
        }

        // Crear 5 suscripciones expiradas
        foreach (range(1, 5) as $index) {
            Subscription::factory()
                ->expired()
                ->create([
                    'customer_id' => $customers->random()->id,
                    'branch_id' => $branches->random()->id,
                    'vehicle_type_id' => $vehicleTypes->random()->id,
                    'subscription_type_id' => $subscriptionTypes->random()->id,
                ]);
        }

        // Crear 5 suscripciones inactivas (no expiradas, pero marcadas como inactivas)
        foreach (range(1, 5) as $index) {
            Subscription::factory()
                ->inactive()
                ->create([
                    'customer_id' => $customers->random()->id,
                    'branch_id' => $branches->random()->id,
                    'vehicle_type_id' => $vehicleTypes->random()->id,
                    'subscription_type_id' => $subscriptionTypes->random()->id,
                ]);
        }

        $this->command->info('✓ Created 20 subscriptions: 10 active, 5 expired, 5 inactive');
    }
}
