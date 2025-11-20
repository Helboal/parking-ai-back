<?php

namespace Tests\Feature;

use App\Models\EntryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EntryTypeTest extends TestCase
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
     * Test index - list all entry types
     */
    public function test_index_returns_all_entry_types(): void
    {
        // Crear tipos de entrada
        EntryType::create(['code' => 'REGULAR', 'name' => 'Regular']);
        EntryType::create(['code' => 'SUBSCRIPTION', 'name' => 'Suscripción']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/entry-types');

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
                'message' => 'Listado de tipos de entrada',
                'code' => 200,
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    /**
     * Test store - create new entry type
     */
    public function test_store_creates_entry_type_successfully(): void
    {
        $data = [
            'name' => 'Temporal',
            'code' => 'TEMP',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entry-types', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code', 'created_at', 'updated_at'],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de entrada creado exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Temporal',
                    'code' => 'TEMP',
                ],
            ]);

        $this->assertDatabaseHas('entry_types', [
            'name' => 'Temporal',
            'code' => 'TEMP',
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entry-types', []);

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
        EntryType::create(['code' => 'REGULAR', 'name' => 'Test']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/entry-types', [
                'name' => 'Regular',
                'code' => 'REGULAR',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['code'],
            ]);
    }

    /**
     * Test show - get single entry type
     */
    public function test_show_returns_entry_type(): void
    {
        $entryType = EntryType::create([
            'code' => 'SUBSCRIPTION',
            'name' => 'Suscripción',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/entry-types/'.$entryType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de entrada encontrado',
                'code' => 200,
                'data' => [
                    'id' => $entryType->id,
                    'name' => 'Suscripción',
                    'code' => 'SUBSCRIPTION',
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/entry-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de entrada no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update entry type
     */
    public function test_update_modifies_entry_type_successfully(): void
    {
        $entryType = EntryType::create([
            'code' => 'REGULAR',
            'name' => 'Regular',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/entry-types/'.$entryType->id, [
                'name' => 'Regular Actualizado',
                'code' => 'REGULAR',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de entrada actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Regular Actualizado',
                    'code' => 'REGULAR',
                ],
            ]);

        $this->assertDatabaseHas('entry_types', [
            'id' => $entryType->id,
            'name' => 'Regular Actualizado',
        ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/entry-types/999', [
                'name' => 'Test',
                'code' => 'TEST',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de entrada no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete entry type
     */
    public function test_destroy_deletes_entry_type_successfully(): void
    {
        $entryType = EntryType::create([
            'name' => 'VIP',
            'code' => 'VIP',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/entry-types/'.$entryType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de entrada eliminado exitosamente',
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('entry_types', [
            'id' => $entryType->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/entry-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de entrada no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/entry-types');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/entry-types', []);
        $response->assertStatus(401);
    }
}
