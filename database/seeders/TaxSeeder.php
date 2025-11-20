<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            ['name' => 'IVA', 'code' => 'VAT', 'percentage' => 19.00, 'is_active' => true],
        ];

        foreach ($taxes as $tax) {
            Tax::firstOrCreate(
                ['code' => $tax['code']],
                [
                    'name' => $tax['name'],
                    'percentage' => $tax['percentage'],
                    'is_active' => $tax['is_active'],
                ]
            );
        }
    }
}
