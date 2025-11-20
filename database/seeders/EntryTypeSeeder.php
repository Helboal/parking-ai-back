<?php

namespace Database\Seeders;

use App\Models\EntryType;
use Illuminate\Database\Seeder;

class EntryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entryTypes = [
            ['name' => 'Regular', 'code' => 'REGULAR'],
            ['name' => 'Suscripción', 'code' => 'SUBSCRIPTION'],
        ];

        foreach ($entryTypes as $entryType) {
            EntryType::firstOrCreate(
                ['code' => $entryType['code']],
                ['name' => $entryType['name']]
            );
        }
    }
}
