<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Juan',
                'last_name' => 'Pérez García',
                'email' => 'superadmin@parking.com',
                'document_type_code' => 'CC',
                'document_number' => '1234567890',
                'phone' => '3001234567',
                'role' => 'Super Administrador',
            ],
            [
                'name' => 'María',
                'last_name' => 'González López',
                'email' => 'admin@parking.com',
                'document_type_code' => 'CC',
                'document_number' => '9876543210',
                'phone' => '3009876543',
                'role' => 'Administrador',
            ],
            [
                'name' => 'Carlos',
                'last_name' => 'Rodríguez Martínez',
                'email' => 'operador@parking.com',
                'document_type_code' => 'CE',
                'document_number' => '5555555555',
                'phone' => '3005555555',
                'role' => 'Operador',
            ],
            [
                'name' => 'Ana',
                'last_name' => 'Ramírez Torres',
                'email' => 'cajero@parking.com',
                'document_type_code' => 'TI',
                'document_number' => '1111111111',
                'phone' => '3001111111',
                'role' => 'Cajero',
            ],
            [
                'name' => 'Luis',
                'last_name' => 'Sánchez Díaz',
                'email' => 'supervisor@parking.com',
                'document_type_code' => 'PP',
                'document_number' => 'AB123456',
                'phone' => '3002222222',
                'role' => 'Supervisor',
            ],
        ];

        foreach ($users as $userData) {
            // Obtener el document_type_id por el código
            $documentType = \App\Models\DocumentType::where('code', $userData['document_type_code'])->first();

            // Crear usuario
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'last_name' => $userData['last_name'],
                    'document_number' => $userData['document_number'],
                    'phone' => $userData['phone'],
                    'is_active' => true,
                    'document_type_id' => $documentType->id,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Asignar rol
            $role = Role::where('name', $userData['role'])->first();
            if ($role && !$user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
