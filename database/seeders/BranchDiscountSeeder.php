<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchDiscount;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class BranchDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $vehicleTypes = VehicleType::all();

        // Define discount configurations: minutes => discount_percentage
        $discountConfigs = [
            ['minutes' => 120, 'discount_percentage' => 10],  // 10% discount after 2 hours
            ['minutes' => 240, 'discount_percentage' => 15],  // 15% discount after 4 hours
            ['minutes' => 480, 'discount_percentage' => 20],  // 20% discount after 8 hours
        ];

        foreach ($branches as $branch) {
            foreach ($vehicleTypes as $vehicleType) {
                foreach ($discountConfigs as $config) {
                    BranchDiscount::firstOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'vehicle_type_id' => $vehicleType->id,
                            'minutes' => $config['minutes'],
                        ],
                        [
                            'discount_percentage' => $config['discount_percentage'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
