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
            'CAR' => ['minutes_threshold' => 720, 'flat_rate_amount' => 30000.00],  // 12 hours = $30,000 COP for cars
            'MOTO' => ['minutes_threshold' => 720, 'flat_rate_amount' => 20000.00], // 12 hours = $20,000 COP for motorcycles
        ];

        foreach ($branches as $branch) {
            foreach ($vehicleTypes as $vehicleType) {
                $config = $flatRateConfigs[$vehicleType->code] ?? ['minutes_threshold' => 720, 'flat_rate_amount' => 25000.00];

                BranchFlatRate::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'vehicle_type_id' => $vehicleType->id,
                    ],
                    [
                        'minutes_threshold' => $config['minutes_threshold'],
                        'flat_rate_amount' => $config['flat_rate_amount'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
