<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            ['name' => 'Cédula de Ciudadanía', 'code' => 'CC'],
            ['name' => 'Cédula de Extranjería', 'code' => 'CE'],
            ['name' => 'Tarjeta de Identidad', 'code' => 'TI'],
            ['name' => 'NIT', 'code' => 'NIT'],
            ['name' => 'Pasaporte', 'code' => 'PP'],
            ['name' => 'Permiso Especial de Permanencia', 'code' => 'PEP'],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::firstOrCreate(
                ['code' => $documentType['code']],
                ['name' => $documentType['name']]
            );
        }
    }
}
