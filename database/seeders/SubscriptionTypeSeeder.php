<?php

namespace Database\Seeders;

use App\Models\SubscriptionType;
use Illuminate\Database\Seeder;

class SubscriptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptionTypes = [
            ['name' => 'Mensual', 'code' => 'MONTHLY', 'duration_days' => 30],
            ['name' => 'Trimestral', 'code' => 'QUARTERLY', 'duration_days' => 90],
            ['name' => 'Anual', 'code' => 'ANNUAL', 'duration_days' => 365],
        ];

        foreach ($subscriptionTypes as $subscriptionType) {
            SubscriptionType::firstOrCreate(
                ['code' => $subscriptionType['code']],
                [
                    'name' => $subscriptionType['name'],
                    'duration_days' => $subscriptionType['duration_days'],
                ]
            );
        }
    }
}
