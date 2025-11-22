<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que existen tipos de vehículo
        $carType = VehicleType::firstOrCreate(
            ['code' => 'CAR'],
            ['name' => 'Carro']
        );

        $motoType = VehicleType::firstOrCreate(
            ['code' => 'MOTO'],
            ['name' => 'Motocicleta']
        );

        // Obtener clientes aleatorios para asignar vehículos
        $customers = Customer::all();

        // Si no hay clientes, crear algunos
        if ($customers->count() === 0) {
            $customers = Customer::factory()->count(8)->create();
        }

        // Crear 10 carros
        $cars = [
            [
                'license_plate' => 'ABC123',
                'brand' => 'Chevrolet',
                'model' => 'Spark GT',
                'color' => 'Rojo',
                'year' => 2022,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'XYZ789',
                'brand' => 'Renault',
                'model' => 'Logan',
                'color' => 'Blanco',
                'year' => 2021,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'DEF456',
                'brand' => 'Mazda',
                'model' => '3',
                'color' => 'Gris',
                'year' => 2023,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'GHI789',
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'color' => 'Negro',
                'year' => 2020,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'JKL012',
                'brand' => 'Nissan',
                'model' => 'Versa',
                'color' => 'Plata',
                'year' => 2019,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'MNO345',
                'brand' => 'Kia',
                'model' => 'Sportage',
                'color' => 'Azul',
                'year' => 2022,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'PQR678',
                'brand' => 'Hyundai',
                'model' => 'Tucson',
                'color' => 'Blanco',
                'year' => 2021,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'STU901',
                'brand' => 'Ford',
                'model' => 'Escape',
                'color' => 'Verde',
                'year' => 2020,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'VWX234',
                'brand' => null,
                'model' => null,
                'color' => null,
                'year' => null,
                'vehicle_type_id' => $carType->id,
            ],
            [
                'license_plate' => 'YZA567',
                'brand' => 'Suzuki',
                'model' => 'Vitara',
                'color' => 'Rojo',
                'year' => 2023,
                'vehicle_type_id' => $carType->id,
            ],
        ];

        // Crear 5 motos
        $motorcycles = [
            [
                'license_plate' => 'ABC12D',
                'brand' => 'Yamaha',
                'model' => 'FZ',
                'color' => 'Negro',
                'year' => 2022,
                'vehicle_type_id' => $motoType->id,
            ],
            [
                'license_plate' => 'XYZ34E',
                'brand' => 'Honda',
                'model' => 'CB 190',
                'color' => 'Rojo',
                'year' => 2021,
                'vehicle_type_id' => $motoType->id,
            ],
            [
                'license_plate' => 'DEF56G',
                'brand' => 'Suzuki',
                'model' => 'Gixxer',
                'color' => 'Azul',
                'year' => 2020,
                'vehicle_type_id' => $motoType->id,
            ],
            [
                'license_plate' => 'GHI78H',
                'brand' => null,
                'model' => null,
                'color' => null,
                'year' => null,
                'vehicle_type_id' => $motoType->id,
            ],
            [
                'license_plate' => 'JKL90I',
                'brand' => 'AKT',
                'model' => 'NKD 125',
                'color' => 'Negro',
                'year' => 2023,
                'vehicle_type_id' => $motoType->id,
            ],
        ];

        // Combinar todos los vehículos
        $allVehicles = array_merge($cars, $motorcycles);

        // Crear vehículos asignándolos a clientes aleatorios
        foreach ($allVehicles as $index => $vehicleData) {
            // Usar módulo para asegurar que hay suficientes clientes
            $customer = $customers[$index % $customers->count()];

            Vehicle::firstOrCreate(
                ['license_plate' => $vehicleData['license_plate']],
                [
                    'brand' => $vehicleData['brand'],
                    'model' => $vehicleData['model'],
                    'color' => $vehicleData['color'],
                    'year' => $vehicleData['year'],
                    'customer_id' => $customer->id,
                    'vehicle_type_id' => $vehicleData['vehicle_type_id'],
                ]
            );
        }
    }
}
