<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurar que existen tipos de documento
        $cc = DocumentType::firstOrCreate(
            ['code' => 'CC'],
            ['name' => 'Cédula de Ciudadanía']
        );

        $ce = DocumentType::firstOrCreate(
            ['code' => 'CE'],
            ['name' => 'Cédula de Extranjería']
        );

        $nit = DocumentType::firstOrCreate(
            ['code' => 'NIT'],
            ['name' => 'NIT']
        );

        $ti = DocumentType::firstOrCreate(
            ['code' => 'TI'],
            ['name' => 'Tarjeta de Identidad']
        );

        // Cliente 1: Información completa con CC
        Customer::firstOrCreate(
            [
                'document_type_id' => $cc->id,
                'document_number' => '1234567890',
            ],
            [
                'first_name' => 'Juan',
                'last_name' => 'Pérez García',
                'email' => 'juan.perez@example.com',
                'phone' => '6012345678',
                'mobile' => '3001234567',
                'address' => 'Calle 123 #45-67, Bogotá',
                'birth_date' => '1985-03-15',
                'photo_url' => 'https://via.placeholder.com/150?text=Juan',
                'notes' => 'Cliente frecuente, prefiere parqueadero cubierto',
            ]
        );

        // Cliente 2: Información completa con CE
        Customer::firstOrCreate(
            [
                'document_type_id' => $ce->id,
                'document_number' => '9876543210',
            ],
            [
                'first_name' => 'María',
                'last_name' => 'González López',
                'email' => 'maria.gonzalez@example.com',
                'phone' => '6019876543',
                'mobile' => '3109876543',
                'address' => 'Carrera 45 #67-89, Medellín',
                'birth_date' => '1990-07-22',
                'photo_url' => null,
                'notes' => null,
            ]
        );

        // Cliente 3: Solo datos básicos
        Customer::firstOrCreate(
            [
                'document_type_id' => $cc->id,
                'document_number' => '5555555555',
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Martínez',
                'email' => null,
                'phone' => null,
                'mobile' => '3205555555',
                'address' => null,
                'birth_date' => null,
                'photo_url' => null,
                'notes' => null,
            ]
        );

        // Cliente 4: Empresa con NIT
        Customer::firstOrCreate(
            [
                'document_type_id' => $nit->id,
                'document_number' => '900123456-7',
            ],
            [
                'first_name' => 'Transportes',
                'last_name' => 'Rápidos S.A.S.',
                'email' => 'info@transportesrapidos.com',
                'phone' => '6011112222',
                'mobile' => '3151112222',
                'address' => 'Avenida 68 #123-45, Bogotá',
                'birth_date' => null,
                'photo_url' => null,
                'notes' => 'Cliente corporativo, facturación mensual',
            ]
        );

        // Cliente 5: Sin email
        Customer::firstOrCreate(
            [
                'document_type_id' => $cc->id,
                'document_number' => '7777777777',
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Rodríguez',
                'email' => null,
                'phone' => '6017777777',
                'mobile' => '3007777777',
                'address' => 'Calle 50 #23-45',
                'birth_date' => '1995-11-30',
                'photo_url' => null,
                'notes' => null,
            ]
        );

        // Cliente 6: Menor de edad con TI
        Customer::firstOrCreate(
            [
                'document_type_id' => $ti->id,
                'document_number' => '1122334455',
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Sánchez Torres',
                'email' => 'pedro.sanchez@example.com',
                'phone' => null,
                'mobile' => '3111122334',
                'address' => 'Carrera 10 #15-20',
                'birth_date' => '2008-05-10',
                'photo_url' => null,
                'notes' => 'Representado por su padre',
            ]
        );

        // Cliente 7: Información parcial
        Customer::firstOrCreate(
            [
                'document_type_id' => $cc->id,
                'document_number' => '8888888888',
            ],
            [
                'first_name' => 'Luis',
                'last_name' => 'Ramírez',
                'email' => 'luis.ramirez@example.com',
                'phone' => null,
                'mobile' => null,
                'address' => null,
                'birth_date' => null,
                'photo_url' => null,
                'notes' => 'Nuevo cliente',
            ]
        );

        // Clientes 8-10: Generados con factory para variedad
        Customer::factory()->count(3)->create();
    }
}
