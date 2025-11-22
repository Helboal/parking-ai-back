<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\DocumentType;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected $branch;

    protected $vehicle;

    protected $entryType;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear tipo de documento
        $documentType = DocumentType::firstOrCreate(
            ['code' => 'CC'],
            ['name' => 'Cédula de Ciudadanía']
        );

        // Crear tipo de vehículo
        $vehicleType = VehicleType::firstOrCreate(
            ['code' => 'CAR'],
            ['name' => 'Carro']
        );

        // Crear tipo de entrada
        $this->entryType = EntryType::firstOrCreate(
            ['code' => 'REGULAR'],
            ['name' => 'Regular']
        );

        // Crear cliente
        $customer = Customer::factory()->create([
            'document_type_id' => $documentType->id,
        ]);

        // Crear sede
        $this->branch = Branch::factory()->create();

        // Crear vehículo
        $this->vehicle = Vehicle::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $vehicleType->id,
        ]);

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test index - list all entries
     */
    public function test_index_returns_all_entries(): void
    {
        // Crear entradas
        Entry::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'entry_user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/entries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'entry_datetime',
                        'exit_datetime',
                        'total_minutes',
                        'status',
                        'entry_user_id',
                        'exit_user_id',
                        'branch_id',
                        'vehicle_id',
                        'entry_type_id',
                        'subscription_id',
                        'notes',
                        'created_at',
                        'updated_at',
                        'entry_user',
                        'exit_user',
                        'branch' => [
                            'id',
                            'name',
                        ],
                        'vehicle' => [
                            'id',
                            'license_plate',
                        ],
                        'entry_type' => [
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
                'message' => 'Listado de entradas',
                'code' => 200,
            ]);

        // Verificar que hay 3 entradas
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test store - create new entry
     */
    public function test_store_creates_entry_successfully(): void
    {
        $data = [
            'entry_datetime' => '2025-01-21 08:00:00',
            'exit_datetime' => '2025-01-21 18:00:00',
            'total_minutes' => 600,
            'status' => 'completed',
            'entry_user_id' => $this->user->id,
            'exit_user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'notes' => 'Cliente frecuente',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entries', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'entry_datetime',
                    'exit_datetime',
                    'total_minutes',
                    'status',
                    'entry_user_id',
                    'exit_user_id',
                    'branch_id',
                    'vehicle_id',
                    'entry_type_id',
                    'subscription_id',
                    'notes',
                    'created_at',
                    'updated_at',
                    'entry_user',
                    'exit_user',
                    'branch',
                    'vehicle',
                    'entry_type',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Entrada creada exitosamente',
                'code' => 201,
                'data' => [
                    'total_minutes' => 600,
                    'status' => 'completed',
                    'notes' => 'Cliente frecuente',
                ],
            ]);

        $this->assertDatabaseHas('entries', [
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'total_minutes' => 600,
            'status' => 'completed',
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entries', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'entry_datetime',
                    'status',
                    'branch_id',
                    'vehicle_id',
                    'entry_type_id',
                ],
            ]);
    }

    /**
     * Test store - exit_datetime before entry_datetime
     */
    public function test_store_fails_with_exit_datetime_before_entry_datetime(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entries', [
                'entry_datetime' => '2025-01-21 18:00:00',
                'exit_datetime' => '2025-01-21 08:00:00',
                'status' => 'active',
                'branch_id' => $this->branch->id,
                'vehicle_id' => $this->vehicle->id,
                'entry_type_id' => $this->entryType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['exit_datetime'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test store - negative total_minutes
     */
    public function test_store_fails_with_negative_total_minutes(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entries', [
                'entry_datetime' => '2025-01-21 08:00:00',
                'total_minutes' => -10,
                'status' => 'active',
                'branch_id' => $this->branch->id,
                'vehicle_id' => $this->vehicle->id,
                'entry_type_id' => $this->entryType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['total_minutes'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test store - invalid status
     */
    public function test_store_fails_with_invalid_status(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entries', [
                'entry_datetime' => '2025-01-21 08:00:00',
                'status' => 'invalid_status',
                'branch_id' => $this->branch->id,
                'vehicle_id' => $this->vehicle->id,
                'entry_type_id' => $this->entryType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['status'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test store - invalid foreign keys
     */
    public function test_store_fails_with_invalid_foreign_keys(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entries', [
                'entry_datetime' => '2025-01-21 08:00:00',
                'status' => 'active',
                'entry_user_id' => 99999,
                'exit_user_id' => 99999,
                'branch_id' => 99999,
                'vehicle_id' => 99999,
                'entry_type_id' => 99999,
                'subscription_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'entry_user_id',
                    'exit_user_id',
                    'branch_id',
                    'vehicle_id',
                    'entry_type_id',
                    'subscription_id',
                ],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test show - get single entry
     */
    public function test_show_returns_entry(): void
    {
        $entry = Entry::factory()->create([
            'entry_datetime' => '2025-01-21 08:00:00',
            'total_minutes' => 120,
            'status' => 'completed',
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'entry_user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/entries/'.$entry->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Entrada encontrada',
                'code' => 200,
                'data' => [
                    'id' => $entry->id,
                    'total_minutes' => 120,
                    'status' => 'completed',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'entry_user',
                    'exit_user',
                    'branch',
                    'vehicle',
                    'entry_type',
                    'subscription',
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/entries/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Entrada no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update entry
     */
    public function test_update_modifies_entry_successfully(): void
    {
        $entry = Entry::factory()->create([
            'entry_datetime' => '2025-01-21 08:00:00',
            'total_minutes' => 120,
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'entry_user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/entries/'.$entry->id, [
                'entry_datetime' => '2025-01-21 09:00:00',
                'exit_datetime' => '2025-01-21 19:00:00',
                'total_minutes' => 600,
                'status' => 'completed',
                'entry_user_id' => $this->user->id,
                'exit_user_id' => $this->user->id,
                'branch_id' => $this->branch->id,
                'vehicle_id' => $this->vehicle->id,
                'entry_type_id' => $this->entryType->id,
                'notes' => 'Actualizado',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Entrada actualizada exitosamente',
                'code' => 200,
                'data' => [
                    'total_minutes' => 600,
                    'status' => 'completed',
                    'notes' => 'Actualizado',
                ],
            ]);

        $this->assertDatabaseHas('entries', [
            'id' => $entry->id,
            'total_minutes' => 600,
            'status' => 'completed',
        ]);
    }

    /**
     * Test update - validation errors
     */
    public function test_update_fails_with_validation_errors(): void
    {
        $entry = Entry::factory()->create([
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'entry_user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/entries/'.$entry->id, [
                'entry_datetime' => 'invalid-date',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'entry_datetime',
                    'status',
                    'branch_id',
                    'vehicle_id',
                    'entry_type_id',
                ],
            ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/entries/999', [
                'entry_datetime' => '2025-01-21 08:00:00',
                'status' => 'active',
                'branch_id' => $this->branch->id,
                'vehicle_id' => $this->vehicle->id,
                'entry_type_id' => $this->entryType->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Entrada no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete entry
     */
    public function test_destroy_deletes_entry_successfully(): void
    {
        $entry = Entry::factory()->create([
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'entry_user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/entries/'.$entry->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Entrada eliminada exitosamente',
                'code' => 200,
            ]);

        // Verificar que fue eliminada
        $this->assertDatabaseMissing('entries', [
            'id' => $entry->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/entries/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Entrada no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/entries');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/entries', []);
        $response->assertStatus(401);

        $entry = Entry::factory()->create([
            'branch_id' => $this->branch->id,
            'vehicle_id' => $this->vehicle->id,
            'entry_type_id' => $this->entryType->id,
            'entry_user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/admin/entries/'.$entry->id);
        $response->assertStatus(401);

        $response = $this->putJson('/api/admin/entries/'.$entry->id, []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/admin/entries/'.$entry->id);
        $response->assertStatus(401);
    }
}
