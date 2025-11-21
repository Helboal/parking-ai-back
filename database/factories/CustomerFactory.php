<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Obtener o crear tipo de documento CC
        $documentType = DocumentType::firstOrCreate(
            ['code' => 'CC'],
            ['name' => 'Cédula de Ciudadanía']
        );

        // Nombres colombianos comunes
        $maleFirstNames = ['Juan', 'Carlos', 'José', 'Luis', 'Pedro', 'Miguel', 'Andrés', 'Diego', 'Jorge', 'Daniel'];
        $femaleFirstNames = ['María', 'Ana', 'Laura', 'Carmen', 'Luz', 'Patricia', 'Diana', 'Claudia', 'Sandra', 'Mónica'];
        $lastNames = ['García', 'Rodríguez', 'Martínez', 'López', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];

        $isMale = fake()->boolean();
        $firstName = $isMale ? fake()->randomElement($maleFirstNames) : fake()->randomElement($femaleFirstNames);
        $lastName = fake()->randomElement($lastNames).' '.fake()->randomElement($lastNames);

        // 80% tienen email, 20% no
        $hasEmail = fake()->boolean(80);

        // 70% tienen teléfono, 30% no
        $hasPhone = fake()->boolean(70);

        // 90% tienen celular, 10% no
        $hasMobile = fake()->boolean(90);

        // 60% tienen dirección, 40% no
        $hasAddress = fake()->boolean(60);

        // 30% tienen fecha de nacimiento, 70% no
        $hasBirthDate = fake()->boolean(30);

        // 10% tienen foto, 90% no
        $hasPhoto = fake()->boolean(10);

        // 20% tienen notas, 80% no
        $hasNotes = fake()->boolean(20);

        return [
            'document_number' => fake()->unique()->numerify('##########'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $hasEmail ? fake()->unique()->safeEmail() : null,
            'phone' => $hasPhone ? fake()->numerify('60#######') : null,
            'mobile' => $hasMobile ? fake()->numerify('3#########') : null,
            'address' => $hasAddress ? 'Calle '.fake()->numberBetween(1, 200).' #'.fake()->numberBetween(1, 99).'-'.fake()->numberBetween(1, 99) : null,
            'birth_date' => $hasBirthDate ? fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d') : null,
            'photo_url' => $hasPhoto ? 'https://via.placeholder.com/150?text='.$firstName : null,
            'notes' => $hasNotes ? fake()->sentence() : null,
            'document_type_id' => $documentType->id,
        ];
    }

    /**
     * Indicate that the customer has complete information.
     */
    public function complete(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->numerify('60#######'),
                'mobile' => fake()->numerify('3#########'),
                'address' => 'Calle '.fake()->numberBetween(1, 200).' #'.fake()->numberBetween(1, 99).'-'.fake()->numberBetween(1, 99),
                'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
                'photo_url' => 'https://via.placeholder.com/150?text='.$attributes['first_name'],
                'notes' => fake()->sentence(),
            ];
        });
    }

    /**
     * Indicate that the customer has minimal information.
     */
    public function minimal(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'email' => null,
                'phone' => null,
                'mobile' => null,
                'address' => null,
                'birth_date' => null,
                'photo_url' => null,
                'notes' => null,
            ];
        });
    }

    /**
     * Indicate that the customer has a specific document type.
     */
    public function withDocumentType(string $code): Factory
    {
        return $this->state(function (array $attributes) use ($code) {
            $documentType = DocumentType::firstOrCreate(
                ['code' => $code],
                ['name' => $code]
            );

            return [
                'document_type_id' => $documentType->id,
            ];
        });
    }
}
