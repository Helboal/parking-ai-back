<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Obtener o crear tipo de vehículo CAR
        $vehicleType = VehicleType::firstOrCreate(
            ['code' => 'CAR'],
            ['name' => 'Carro']
        );

        // Marcas colombianas comunes
        $brands = [
            'Chevrolet',
            'Renault',
            'Mazda',
            'Toyota',
            'Nissan',
            'Kia',
            'Hyundai',
            'Ford',
            'Volkswagen',
            'Suzuki',
        ];

        // Modelos comunes por marca
        $models = [
            'Chevrolet' => ['Spark', 'Spark GT', 'Sail', 'Onix', 'Tracker', 'Captiva'],
            'Renault' => ['Logan', 'Sandero', 'Duster', 'Kwid', 'Stepway', 'Koleos'],
            'Mazda' => ['2', '3', 'CX-3', 'CX-5', 'CX-30', '6'],
            'Toyota' => ['Corolla', 'Yaris', 'Hilux', 'Fortuner', 'Prado', 'RAV4'],
            'Nissan' => ['Versa', 'Kicks', 'Sentra', 'X-Trail', 'Frontier', 'Qashqai'],
            'Kia' => ['Picanto', 'Rio', 'Sportage', 'Seltos', 'Sorento', 'Cerato'],
            'Hyundai' => ['i10', 'i25', 'Accent', 'Tucson', 'Santa Fe', 'Creta'],
            'Ford' => ['Fiesta', 'Focus', 'Escape', 'Explorer', 'Ranger', 'EcoSport'],
            'Volkswagen' => ['Gol', 'Polo', 'Virtus', 'T-Cross', 'Tiguan', 'Amarok'],
            'Suzuki' => ['Alto', 'Swift', 'Vitara', 'S-Cross', 'Jimny', 'Dzire'],
        ];

        // Colores comunes
        $colors = [
            'Blanco',
            'Negro',
            'Gris',
            'Plata',
            'Rojo',
            'Azul',
            'Amarillo',
            'Verde',
            'Naranja',
            'Café',
        ];

        // Seleccionar marca aleatoria
        $brand = fake()->randomElement($brands);

        // Seleccionar modelo según la marca
        $model = fake()->randomElement($models[$brand]);

        // Generar placa colombiana (formato: ABC123 para carros, ABC12D para motos)
        $letters = strtoupper(fake()->lexify('???'));
        $numbers = fake()->numerify('###');
        $licensePlate = $letters.$numbers;

        // 70% tienen marca, 30% no
        $hasBrand = fake()->boolean(70);

        // 70% tienen modelo, 30% no
        $hasModel = fake()->boolean(70);

        // 60% tienen color, 40% no
        $hasColor = fake()->boolean(60);

        // 80% tienen año, 20% no
        $hasYear = fake()->boolean(80);

        return [
            'license_plate' => strtoupper($licensePlate),
            'brand' => $hasBrand ? $brand : null,
            'model' => $hasModel ? $model : null,
            'color' => $hasColor ? fake()->randomElement($colors) : null,
            'year' => $hasYear ? fake()->numberBetween(2010, 2024) : null,
            'customer_id' => Customer::factory(),
            'vehicle_type_id' => $vehicleType->id,
        ];
    }

    /**
     * Indicate that the vehicle has complete information.
     */
    public function complete(): Factory
    {
        return $this->state(function (array $attributes) {
            $brands = [
                'Chevrolet',
                'Renault',
                'Mazda',
                'Toyota',
                'Nissan',
                'Kia',
                'Hyundai',
                'Ford',
                'Volkswagen',
                'Suzuki',
            ];

            $models = [
                'Chevrolet' => ['Spark', 'Spark GT', 'Sail', 'Onix', 'Tracker', 'Captiva'],
                'Renault' => ['Logan', 'Sandero', 'Duster', 'Kwid', 'Stepway', 'Koleos'],
                'Mazda' => ['2', '3', 'CX-3', 'CX-5', 'CX-30', '6'],
                'Toyota' => ['Corolla', 'Yaris', 'Hilux', 'Fortuner', 'Prado', 'RAV4'],
                'Nissan' => ['Versa', 'Kicks', 'Sentra', 'X-Trail', 'Frontier', 'Qashqai'],
                'Kia' => ['Picanto', 'Rio', 'Sportage', 'Seltos', 'Sorento', 'Cerato'],
                'Hyundai' => ['i10', 'i25', 'Accent', 'Tucson', 'Santa Fe', 'Creta'],
                'Ford' => ['Fiesta', 'Focus', 'Escape', 'Explorer', 'Ranger', 'EcoSport'],
                'Volkswagen' => ['Gol', 'Polo', 'Virtus', 'T-Cross', 'Tiguan', 'Amarok'],
                'Suzuki' => ['Alto', 'Swift', 'Vitara', 'S-Cross', 'Jimny', 'Dzire'],
            ];

            $colors = [
                'Blanco',
                'Negro',
                'Gris',
                'Plata',
                'Rojo',
                'Azul',
                'Amarillo',
                'Verde',
                'Naranja',
                'Café',
            ];

            $brand = fake()->randomElement($brands);
            $model = fake()->randomElement($models[$brand]);

            return [
                'brand' => $brand,
                'model' => $model,
                'color' => fake()->randomElement($colors),
                'year' => fake()->numberBetween(2010, 2024),
            ];
        });
    }

    /**
     * Indicate that the vehicle has minimal information.
     */
    public function minimal(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'brand' => null,
                'model' => null,
                'color' => null,
                'year' => null,
            ];
        });
    }

    /**
     * Indicate that the vehicle is a motorcycle.
     */
    public function motorcycle(): Factory
    {
        return $this->state(function (array $attributes) {
            $vehicleType = VehicleType::firstOrCreate(
                ['code' => 'MOTO'],
                ['name' => 'Motocicleta']
            );

            $brands = ['Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'Bajaj', 'AKT', 'Auteco', 'TVS'];
            $models = [
                'Yamaha' => ['FZ', 'R15', 'MT-03', 'XTZ 125', 'Crypton'],
                'Honda' => ['CB 190', 'CBR 250', 'XR 190', 'Wave', 'PCX'],
                'Suzuki' => ['Gixxer', 'GSX-R', 'V-Strom', 'AX 100', 'GN 125'],
                'Kawasaki' => ['Ninja', 'Z400', 'Versys', 'KLX 150', 'KX 250'],
                'Bajaj' => ['Pulsar', 'Dominar', 'Boxer', 'CT 100', 'Avenger'],
                'AKT' => ['NKD 125', 'TT 150', 'CR5 180', 'Dynamic', 'EVO'],
                'Auteco' => ['Victory', 'Platino', 'Mobility', 'Triumph', 'Bajaj'],
                'TVS' => ['Apache', 'Sport', 'Star', 'King', 'Radeon'],
            ];

            $brand = fake()->randomElement($brands);
            $model = fake()->randomElement($models[$brand]);

            // Generar placa de moto (formato: ABC12D)
            $letters = strtoupper(fake()->lexify('???'));
            $numbers = fake()->numerify('##');
            $lastLetter = strtoupper(fake()->randomLetter());
            $licensePlate = $letters.$numbers.$lastLetter;

            return [
                'license_plate' => strtoupper($licensePlate),
                'brand' => $brand,
                'model' => $model,
                'vehicle_type_id' => $vehicleType->id,
            ];
        });
    }
}
