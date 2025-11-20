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

        // Define discount configurations: minuts => discount_percentage
        $discountConfigs = [
            ['minuts' => 120, 'discount_percentage' => 10],  // 10% discount after 2 hours
            ['minuts' => 240, 'discount_percentage' => 15],  // 15% discount after 4 hours
            ['minuts' => 480, 'discount_percentage' => 20],  // 20% discount after 8 hours
        ];

        foreach ($branches as $branch) {
            foreach ($vehicleTypes as $vehicleType) {
                foreach ($discountConfigs as $config) {
                    BranchDiscount::firstOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'vehicle_type_id' => $vehicleType->id,
                            'minuts' => $config['minuts'],
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
