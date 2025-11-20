<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchFlatRate;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class BranchFlatRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $vehicleTypes = VehicleType::all();

        // Define flat rate configurations per vehicle type
        $flatRateConfigs = [
            'CAR' => ['minuts_threshold' => 720, 'flat_rate' => 30000.00],  // 12 hours = $30,000 COP for cars
            'MOTO' => ['minuts_threshold' => 720, 'flat_rate' => 20000.00], // 12 hours = $20,000 COP for motorcycles
        ];

        foreach ($branches as $branch) {
            foreach ($vehicleTypes as $vehicleType) {
                $config = $flatRateConfigs[$vehicleType->code] ?? ['minuts_threshold' => 720, 'flat_rate' => 25000.00];

                BranchFlatRate::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'vehicle_type_id' => $vehicleType->id,
                    ],
                    [
                        'minuts_threshold' => $config['minuts_threshold'],
                        'flat_rate' => $config['flat_rate'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
