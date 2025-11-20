<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios para asignar como responsables
        $users = User::all();

        $branches = [
            [
                'name' => 'Sede Norte',
                'address' => 'Calle 100 # 15-20, Bogotá',
                'phone' => '3001234567',
                'total_spaces' => 150,
                'available_spaces' => 150,
                'is_active' => true,
                'user_id' => $users->get(0)?->id,
            ],
            [
                'name' => 'Sede Sur',
                'address' => 'Carrera 30 # 45-80, Bogotá',
                'phone' => '3009876543',
                'total_spaces' => 200,
                'available_spaces' => 200,
                'is_active' => true,
                'user_id' => $users->get(1)?->id,
            ],
            [
                'name' => 'Sede Centro',
                'address' => 'Avenida Jiménez # 7-35, Bogotá',
                'phone' => '3005555555',
                'total_spaces' => 80,
                'available_spaces' => 80,
                'is_active' => true,
                'user_id' => $users->get(2)?->id,
            ],
            [
                'name' => 'Sede Occidente',
                'address' => 'Calle 13 # 68-50, Bogotá',
                'phone' => '3002222222',
                'total_spaces' => 120,
                'available_spaces' => 120,
                'is_active' => false,
                'user_id' => null,
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(
                ['name' => $branchData['name']],
                $branchData
            );
        }
    }
}
