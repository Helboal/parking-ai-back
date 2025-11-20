<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchRate;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class BranchRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $vehicleTypes = VehicleType::all();

        // Define rate configurations per vehicle type (rate per minute in COP)
        $rateConfig = [
            'CAR' => 50.00,   // $50 COP per minute for cars
            'MOTO' => 30.00,  // $30 COP per minute for motorcycles
        ];

        foreach ($branches as $branch) {
            foreach ($vehicleTypes as $vehicleType) {
                $rate = $rateConfig[$vehicleType->code] ?? 40.00;

                BranchRate::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'vehicle_type_id' => $vehicleType->id,
                    ],
                    [
                        'rate_per_minute' => $rate,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
