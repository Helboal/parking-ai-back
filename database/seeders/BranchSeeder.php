<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Sede Norte',
                'code' => 'NORTE',
                'address' => 'Calle 100 # 15-20, Bogotá',
                'phone' => '3001234567',
                'email' => 'norte@parqueadero.com',
                'opening_time' => '06:00',
                'closing_time' => '22:00',
                'is_active' => true,
            ],
            [
                'name' => 'Sede Sur',
                'code' => 'SUR',
                'address' => 'Carrera 30 # 45-80, Bogotá',
                'phone' => '3009876543',
                'email' => 'sur@parqueadero.com',
                'opening_time' => '07:00',
                'closing_time' => '23:00',
                'is_active' => true,
            ],
            [
                'name' => 'Sede Centro',
                'code' => 'CENTRO',
                'address' => 'Avenida Jiménez # 7-35, Bogotá',
                'phone' => '3005555555',
                'email' => 'centro@parqueadero.com',
                'opening_time' => '05:00',
                'closing_time' => '20:00',
                'is_active' => true,
            ],
            [
                'name' => 'Sede Oriente',
                'code' => 'ORIENTE',
                'address' => 'Calle 13 # 68-50, Bogotá',
                'phone' => '3002222222',
                'email' => 'oriente@parqueadero.com',
                'opening_time' => '06:00',
                'closing_time' => '21:00',
                'is_active' => false,
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(
                ['code' => $branchData['code']],
                $branchData
            );
        }
    }
}
