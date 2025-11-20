<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleTypes = [
            ['name' => 'Carro', 'code' => 'CAR'],
            ['name' => 'Moto', 'code' => 'MOTO'],
        ];

        foreach ($vehicleTypes as $vehicleType) {
            VehicleType::firstOrCreate(
                ['code' => $vehicleType['code']],
                ['name' => $vehicleType['name']]
            );
        }
    }
}
