<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchTax;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class BranchTaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $taxes = Tax::all();

        // Assign all taxes to all branches by default
        foreach ($branches as $branch) {
            foreach ($taxes as $tax) {
                BranchTax::firstOrCreate([
                    'branch_id' => $branch->id,
                    'tax_id' => $tax->id,
                ]);
            }
        }
    }
}
