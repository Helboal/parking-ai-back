<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles del sistema
        $roles = [
            ['name' => 'Super Administrador', 'guard_name' => 'sanctum'],
            ['name' => 'Administrador', 'guard_name' => 'sanctum'],
            ['name' => 'Operador', 'guard_name' => 'sanctum'],
            ['name' => 'Cajero', 'guard_name' => 'sanctum'],
            ['name' => 'Supervisor', 'guard_name' => 'sanctum'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']]
            );
        }
    }
}
