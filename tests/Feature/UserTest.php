<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected $role;

    protected $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol y tipo de documento
        $this->role = Role::create(['name' => 'Super Administrador', 'guard_name' => 'sanctum']);
        $this->documentType = DocumentType::create(['name' => 'Cédula de Ciudadanía', 'code' => 'CC']);

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Administrador');
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test index - list all users
     */
    public function test_index_returns_all_users(): void
    {
        // Crear usuarios adicionales
        $user1 = User::factory()->create();
        $user1->assignRole('Super Administrador');

        $user2 = User::factory()->create();
        $user2->assignRole('Super Administrador');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'last_name',
                        'email',
                        'document_number',
                        'phone',
                        'is_active',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                        'document_type' => [
                            'id',
                            'name',
                            'code',
                        ],
                        'role' => [
                            'id',
                            'name',
                            'guard_name',
                            'permissions',
                        ],
                    ],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de usuarios',
                'code' => 200,
            ]);

        // Verificar que hay 3 usuarios (setUp + 2 creados en el test)
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test store - create new user
     */
    public function test_store_creates_user_successfully(): void
    {
        $data = [
            'name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'password' => 'password123',
            'document_number' => '1234567890',
            'document_type_id' => $this->documentType->id,
            'phone' => '3001234567',
            'is_active' => true,
            'role_id' => $this->role->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/users', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'last_name',
                    'email',
                    'document_number',
                    'phone',
                    'is_active',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'document_type',
                    'role',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Juan',
                    'last_name' => 'Pérez',
                    'email' => 'juan.perez@example.com',
                    'document_number' => '1234567890',
                    'phone' => '3001234567',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez@example.com',
            'document_number' => '1234567890',
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/users', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'name',
                    'last_name',
                    'email',
                    'password',
                    'document_number',
                    'document_type_id',
                    'role_id',
                ],
            ]);
    }

    /**
     * Test store - duplicate document number for same document type
     */
    public function test_store_fails_with_duplicate_document(): void
    {
        $existingUser = User::factory()->create([
            'document_type_id' => $this->documentType->id,
            'document_number' => '1234567890',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/users', [
                'name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'juan.perez@example.com',
                'password' => 'password123',
                'document_number' => '1234567890',
                'document_type_id' => $this->documentType->id,
                'phone' => '3001234567',
                'role_id' => $this->role->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['document_number'],
            ]);
    }

    /**
     * Test store - allows same document_number with different document_type
     */
    public function test_store_allows_same_document_number_with_different_type(): void
    {
        // Crear un segundo tipo de documento
        $secondDocType = DocumentType::firstOrCreate(
            ['code' => 'NIT'],
            ['name' => 'NIT']
        );

        // Crear usuario con documento CC 123456
        $existingUser = User::factory()->create([
            'document_type_id' => $this->documentType->id, // CC
            'document_number' => '123456',
        ]);

        // Intentar crear otro usuario con el MISMO número pero tipo NIT
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/users', [
                'name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'juan.perez@example.com',
                'password' => 'password123',
                'document_number' => '123456', // Mismo número
                'document_type_id' => $secondDocType->id, // Diferente tipo
                'phone' => '3001234567',
                'role_id' => $this->role->id,
            ]);

        // Debe permitirlo porque el índice es compuesto
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
            ]);
    }

    /**
     * Test store - duplicate email
     */
    public function test_store_fails_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/users', [
                'name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'document_number' => '9876543210',
                'document_type_id' => $this->documentType->id,
                'phone' => '3001234567',
                'role_id' => $this->role->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['email'],
            ]);
    }

    /**
     * Test show - get single user
     */
    public function test_show_returns_user(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.user@example.com',
        ]);
        $testUser->assignRole('Super Administrador');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/users/'.$testUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario encontrado',
                'code' => 200,
                'data' => [
                    'id' => $testUser->id,
                    'email' => 'test.user@example.com',
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/users/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update user
     */
    public function test_update_modifies_user_successfully(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
        ]);
        $testUser->assignRole('Super Administrador');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/users/'.$testUser->id, [
                'name' => 'Updated',
                'last_name' => 'Name',
                'email' => 'updated@example.com',
                'document_number' => $testUser->document_number,
                'document_type_id' => $testUser->document_type_id,
                'phone' => $testUser->phone,
                'is_active' => true,
                'role_id' => $this->role->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Updated',
                    'email' => 'updated@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $testUser->id,
            'name' => 'Updated',
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/users/999', [
                'name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'document_number' => '1234567890',
                'document_type_id' => $this->documentType->id,
                'role_id' => $this->role->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete user (soft delete)
     */
    public function test_destroy_deletes_user_successfully(): void
    {
        $testUser = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/users/'.$testUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
                'code' => 200,
            ]);

        // Verificar que fue soft deleted
        $this->assertSoftDeleted('users', [
            'id' => $testUser->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/users/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/users', []);
        $response->assertStatus(401);
    }
}
