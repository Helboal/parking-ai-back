<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleTypeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol y usuario autenticado
        Role::create(['name' => 'Super Administrador', 'guard_name' => 'sanctum']);
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Administrador');
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test index - list all vehicle types
     */
    public function test_index_returns_all_vehicle_types(): void
    {
        // Crear tipos de vehículo
        VehicleType::create(['code' => 'CAR', 'name' => 'Carro']);
        VehicleType::create(['code' => 'MOTO', 'name' => 'Moto']);
        VehicleType::create(['code' => 'TRUCK', 'name' => 'Camión']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/vehicle-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'created_at', 'updated_at'],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de tipos de vehículo',
                'code' => 200,
            ]);

        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /**
     * Test store - create new vehicle type
     */
    public function test_store_creates_vehicle_type_successfully(): void
    {
        $data = [
            'name' => 'Camioneta',
            'code' => 'VAN',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/vehicle-types', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code', 'created_at', 'updated_at'],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de vehículo creado exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Camioneta',
                    'code' => 'VAN',
                ],
            ]);

        $this->assertDatabaseHas('vehicle_types', [
            'name' => 'Camioneta',
            'code' => 'VAN',
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/vehicle-types', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => ['name', 'code'],
            ]);
    }

    /**
     * Test store - unique code validation
     */
    public function test_store_fails_with_duplicate_code(): void
    {
        VehicleType::create(['code' => 'CAR', 'name' => 'Test']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/vehicle-types', [
                'name' => 'Carro',
                'code' => 'CAR',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['code'],
            ]);
    }

    /**
     * Test show - get single vehicle type
     */
    public function test_show_returns_vehicle_type(): void
    {
        $vehicleType = VehicleType::create([
            'code' => 'MOTO',
            'name' => 'Motocicleta',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/vehicle-types/'.$vehicleType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de vehículo encontrado',
                'code' => 200,
                'data' => [
                    'id' => $vehicleType->id,
                    'name' => 'Motocicleta',
                    'code' => 'MOTO',
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/vehicle-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de vehículo no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update vehicle type
     */
    public function test_update_modifies_vehicle_type_successfully(): void
    {
        $vehicleType = VehicleType::create([
            'code' => 'TRUCK',
            'name' => 'Camión',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/vehicle-types/'.$vehicleType->id, [
                'name' => 'Camión Actualizado',
                'code' => 'TRUCK',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de vehículo actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Camión Actualizado',
                    'code' => 'TRUCK',
                ],
            ]);

        $this->assertDatabaseHas('vehicle_types', [
            'id' => $vehicleType->id,
            'name' => 'Camión Actualizado',
        ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/vehicle-types/999', [
                'name' => 'Test',
                'code' => 'TEST',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de vehículo no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete vehicle type
     */
    public function test_destroy_deletes_vehicle_type_successfully(): void
    {
        $vehicleType = VehicleType::create([
            'name' => 'Bicicleta',
            'code' => 'BIKE',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/vehicle-types/'.$vehicleType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de vehículo eliminado exitosamente',
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('vehicle_types', [
            'id' => $vehicleType->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/vehicle-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de vehículo no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/vehicle-types');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/vehicle-types', []);
        $response->assertStatus(401);
    }
}
