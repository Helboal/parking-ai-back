<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected $customer;

    protected $vehicleType;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear tipo de documento
        $documentType = DocumentType::firstOrCreate(
            ['code' => 'CC'],
            ['name' => 'Cédula de Ciudadanía']
        );

        // Crear tipo de vehículo
        $this->vehicleType = VehicleType::firstOrCreate(
            ['code' => 'CAR'],
            ['name' => 'Carro']
        );

        // Crear cliente
        $this->customer = Customer::factory()->create([
            'document_type_id' => $documentType->id,
        ]);

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test index - list all vehicles
     */
    public function test_index_returns_all_vehicles(): void
    {
        // Crear vehículos
        Vehicle::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/vehicles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'license_plate',
                        'brand',
                        'model',
                        'color',
                        'year',
                        'customer_id',
                        'vehicle_type_id',
                        'created_at',
                        'updated_at',
                        'customer' => [
                            'id',
                            'first_name',
                            'last_name',
                        ],
                        'vehicle_type' => [
                            'id',
                            'name',
                            'code',
                        ],
                    ],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de vehículos',
                'code' => 200,
            ]);

        // Verificar que hay 3 vehículos
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test store - create new vehicle
     */
    public function test_store_creates_vehicle_successfully(): void
    {
        $data = [
            'license_plate' => 'abc123',
            'brand' => 'Chevrolet',
            'model' => 'Spark GT',
            'color' => 'Rojo',
            'year' => 2022,
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/vehicles', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'license_plate',
                    'brand',
                    'model',
                    'color',
                    'year',
                    'customer_id',
                    'vehicle_type_id',
                    'created_at',
                    'updated_at',
                    'customer',
                    'vehicle_type',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Vehículo creado exitosamente',
                'code' => 201,
                'data' => [
                    'license_plate' => 'ABC123', // Verificar que se convirtió a mayúsculas
                    'brand' => 'Chevrolet',
                    'model' => 'Spark GT',
                    'color' => 'Rojo',
                    'year' => 2022,
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'license_plate' => 'ABC123',
            'brand' => 'Chevrolet',
            'customer_id' => $this->customer->id,
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/vehicles', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'license_plate',
                    'customer_id',
                    'vehicle_type_id',
                ],
            ]);
    }

    /**
     * Test store - duplicate license plate
     */
    public function test_store_fails_with_duplicate_license_plate(): void
    {
        $existingVehicle = Vehicle::factory()->create([
            'license_plate' => 'ABC123',
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/vehicles', [
                'license_plate' => 'abc123', // Minúsculas
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'customer_id' => $this->customer->id,
                'vehicle_type_id' => $this->vehicleType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['license_plate'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test store - converts license plate to uppercase
     */
    public function test_store_converts_license_plate_to_uppercase(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/vehicles', [
                'license_plate' => 'xyz789',
                'customer_id' => $this->customer->id,
                'vehicle_type_id' => $this->vehicleType->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'license_plate' => 'XYZ789',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'license_plate' => 'XYZ789',
        ]);
    }

    /**
     * Test show - get single vehicle
     */
    public function test_show_returns_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create([
            'license_plate' => 'TEST123',
            'brand' => 'Mazda',
            'model' => '3',
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/vehicles/'.$vehicle->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Vehículo encontrado',
                'code' => 200,
                'data' => [
                    'id' => $vehicle->id,
                    'license_plate' => 'TEST123',
                    'brand' => 'Mazda',
                    'model' => '3',
                ],
            ])
            ->assertJsonStructure([
                'data' => ['customer', 'vehicle_type'],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/vehicles/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Vehículo no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update vehicle
     */
    public function test_update_modifies_vehicle_successfully(): void
    {
        $vehicle = Vehicle::factory()->create([
            'license_plate' => 'OLD123',
            'brand' => 'Original',
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/vehicles/'.$vehicle->id, [
                'license_plate' => 'new456',
                'brand' => 'Updated',
                'model' => 'New Model',
                'color' => 'Azul',
                'year' => 2023,
                'customer_id' => $this->customer->id,
                'vehicle_type_id' => $this->vehicleType->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Vehículo actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'license_plate' => 'NEW456',
                    'brand' => 'Updated',
                    'model' => 'New Model',
                    'color' => 'Azul',
                    'year' => 2023,
                ],
            ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'license_plate' => 'NEW456',
            'brand' => 'Updated',
        ]);
    }

    /**
     * Test update - duplicate license plate
     */
    public function test_update_fails_with_duplicate_license_plate(): void
    {
        $vehicle1 = Vehicle::factory()->create([
            'license_plate' => 'AAA111',
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $vehicle2 = Vehicle::factory()->create([
            'license_plate' => 'BBB222',
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        // Intentar actualizar vehicle2 con la placa de vehicle1
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/vehicles/'.$vehicle2->id, [
                'license_plate' => 'AAA111',
                'customer_id' => $this->customer->id,
                'vehicle_type_id' => $this->vehicleType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['license_plate'],
            ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/vehicles/999', [
                'license_plate' => 'TEST123',
                'customer_id' => $this->customer->id,
                'vehicle_type_id' => $this->vehicleType->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Vehículo no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete vehicle
     */
    public function test_destroy_deletes_vehicle_successfully(): void
    {
        $vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/vehicles/'.$vehicle->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Vehículo eliminado exitosamente',
                'code' => 200,
            ]);

        // Verificar que fue eliminado
        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehicle->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/vehicles/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Vehículo no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/vehicles');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/vehicles', []);
        $response->assertStatus(401);

        $vehicle = Vehicle::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->getJson('/api/admin/vehicles/'.$vehicle->id);
        $response->assertStatus(401);

        $response = $this->putJson('/api/admin/vehicles/'.$vehicle->id, []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/admin/vehicles/'.$vehicle->id);
        $response->assertStatus(401);
    }

    /**
     * Test cascade delete - deleting customer deletes vehicles
     */
    public function test_deleting_customer_deletes_vehicles(): void
    {
        // Crear un nuevo customer con vehículos
        $customer = Customer::factory()->create();

        $vehicle1 = Vehicle::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $vehicle2 = Vehicle::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        // Verificar que existen
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle1->id]);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle2->id]);

        // Eliminar el cliente
        $customer->delete();

        // Verificar que los vehículos fueron eliminados en cascada
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle1->id]);
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle2->id]);
    }
}
