<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchParkingCapacity;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class BranchParkingCapacitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $vehicleTypes = VehicleType::all();

        // Define capacity configurations per vehicle type
        $capacityConfig = [
            'CAR' => ['total_spaces' => 50, 'occupied_spaces' => 10],
            'MOTO' => ['total_spaces' => 30, 'occupied_spaces' => 5],
        ];

        foreach ($branches as $branch) {
            foreach ($vehicleTypes as $vehicleType) {
                $config = $capacityConfig[$vehicleType->code] ?? ['total_spaces' => 20, 'occupied_spaces' => 0];

                BranchParkingCapacity::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'vehicle_type_id' => $vehicleType->id,
                    ],
                    [
                        'total_spaces' => $config['total_spaces'],
                        'occupied_spaces' => $config['occupied_spaces'],
                    ]
                );
            }
        }
    }
}
