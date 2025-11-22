<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class EntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que existen las entidades necesarias
        $users = User::all();
        $branches = Branch::all();
        $vehicles = Vehicle::all();
        $entryTypes = EntryType::all();

        // Si no hay suficientes datos, mostrar advertencia
        if ($users->count() === 0 || $branches->count() === 0 || $vehicles->count() === 0 || $entryTypes->count() === 0) {
            $this->command->warn('No hay suficientes datos. Asegúrese de ejecutar primero los seeders de Users, Branches, Vehicles y EntryTypes.');

            return;
        }

        // Crear 10 entradas activas (sin salida)
        $this->command->info('Creando 10 entradas activas...');
        Entry::factory()
            ->count(10)
            ->active()
            ->create();

        // Crear 15 entradas completadas (con salida)
        $this->command->info('Creando 15 entradas completadas...');
        Entry::factory()
            ->count(15)
            ->completed()
            ->create();

        // Crear 5 entradas canceladas
        $this->command->info('Creando 5 entradas canceladas...');
        Entry::factory()
            ->count(5)
            ->cancelled()
            ->create();

        // Intentar crear 5 entradas con suscripción
        $this->command->info('Creando entradas con suscripción...');
        $subscriptions = Subscription::where('is_active', true)->get();

        if ($subscriptions->count() > 0) {
            // Crear 5 entradas con suscripción (mezcla de activas y completadas)
            Entry::factory()
                ->count(3)
                ->active()
                ->withSubscription()
                ->create();

            Entry::factory()
                ->count(2)
                ->completed()
                ->withSubscription()
                ->create();

            $this->command->info('✓ Creadas 5 entradas con suscripción');
        } else {
            $this->command->warn('No hay suscripciones activas. Ejecute SubscriptionSeeder primero.');
        }

        $totalEntries = Entry::count();
        $this->command->info("✓ Total de entradas creadas: {$totalEntries}");
    }
}
