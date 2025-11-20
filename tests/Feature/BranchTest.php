<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol
        Role::create(['name' => 'Super Administrador', 'guard_name' => 'sanctum']);

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Administrador');
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test index - list all branches
     */
    public function test_index_returns_all_branches(): void
    {
        // Crear sedes
        $branch1 = Branch::create([
            'name' => 'Sede Norte',
            'address' => 'Calle 100 # 15-20',
            'phone' => '3001234567',
            'total_spaces' => 150,
            'available_spaces' => 150,
            'is_active' => true,
        ]);

        $branch2 = Branch::create([
            'name' => 'Sede Sur',
            'address' => 'Carrera 30 # 45-80',
            'phone' => '3009876543',
            'total_spaces' => 200,
            'available_spaces' => 180,
            'is_active' => true,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/branches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'address',
                        'phone',
                        'total_spaces',
                        'available_spaces',
                        'is_active',
                        'created_at',
                        'updated_at',
                        'user',
                    ],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de sedes',
                'code' => 200,
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    /**
     * Test store - create new branch
     */
    public function test_store_creates_branch_successfully(): void
    {
        $data = [
            'name' => 'Sede Centro',
            'address' => 'Avenida Jiménez # 7-35',
            'phone' => '3005555555',
            'total_spaces' => 100,
            'available_spaces' => 100,
            'is_active' => true,
            'user_id' => $this->user->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'address',
                    'phone',
                    'total_spaces',
                    'available_spaces',
                    'is_active',
                    'created_at',
                    'updated_at',
                    'user',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Sede creada exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Sede Centro',
                    'address' => 'Avenida Jiménez # 7-35',
                    'phone' => '3005555555',
                    'total_spaces' => 100,
                    'available_spaces' => 100,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'Sede Centro',
            'total_spaces' => 100,
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'name',
                    'address',
                    'total_spaces',
                    'available_spaces',
                ],
            ]);
    }

    /**
     * Test store - available_spaces greater than total_spaces
     */
    public function test_store_fails_when_available_spaces_exceeds_total_spaces(): void
    {
        $data = [
            'name' => 'Sede Test',
            'address' => 'Calle Test',
            'total_spaces' => 100,
            'available_spaces' => 150,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['available_spaces'],
            ]);
    }

    /**
     * Test store - duplicate name
     */
    public function test_store_fails_with_duplicate_name(): void
    {
        Branch::create([
            'name' => 'Sede Duplicada',
            'address' => 'Calle 1',
            'total_spaces' => 100,
            'available_spaces' => 100,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', [
                'name' => 'Sede Duplicada',
                'address' => 'Calle 2',
                'total_spaces' => 50,
                'available_spaces' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['name'],
            ]);
    }

    /**
     * Test show - get single branch
     */
    public function test_show_returns_branch(): void
    {
        $branch = Branch::create([
            'name' => 'Sede Test',
            'address' => 'Calle Test # 10-20',
            'phone' => '3001111111',
            'total_spaces' => 80,
            'available_spaces' => 70,
            'is_active' => true,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/branches/'.$branch->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sede encontrada',
                'code' => 200,
                'data' => [
                    'id' => $branch->id,
                    'name' => 'Sede Test',
                    'address' => 'Calle Test # 10-20',
                    'total_spaces' => 80,
                    'available_spaces' => 70,
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/branches/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Sede no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update branch
     */
    public function test_update_modifies_branch_successfully(): void
    {
        $branch = Branch::create([
            'name' => 'Sede Original',
            'address' => 'Calle Original',
            'total_spaces' => 100,
            'available_spaces' => 100,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/branches/'.$branch->id, [
                'name' => 'Sede Actualizada',
                'address' => 'Calle Actualizada',
                'phone' => '3002222222',
                'total_spaces' => 120,
                'available_spaces' => 110,
                'is_active' => true,
                'user_id' => $this->user->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sede actualizada exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Sede Actualizada',
                    'address' => 'Calle Actualizada',
                    'total_spaces' => 120,
                    'available_spaces' => 110,
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Sede Actualizada',
        ]);
    }

    /**
     * Test update - validation available_spaces
     */
    public function test_update_fails_when_available_spaces_exceeds_total_spaces(): void
    {
        $branch = Branch::create([
            'name' => 'Sede Test',
            'address' => 'Calle Test',
            'total_spaces' => 100,
            'available_spaces' => 90,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/branches/'.$branch->id, [
                'name' => 'Sede Test',
                'address' => 'Calle Test',
                'total_spaces' => 100,
                'available_spaces' => 150,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['available_spaces'],
            ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/branches/999', [
                'name' => 'Test',
                'address' => 'Test',
                'total_spaces' => 100,
                'available_spaces' => 100,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Sede no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete branch (soft delete)
     */
    public function test_destroy_deletes_branch_successfully(): void
    {
        $branch = Branch::create([
            'name' => 'Sede a Eliminar',
            'address' => 'Calle Test',
            'total_spaces' => 50,
            'available_spaces' => 50,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/branches/'.$branch->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sede eliminada exitosamente',
                'code' => 200,
            ]);

        // Verificar que fue soft deleted
        $this->assertSoftDeleted('branches', [
            'id' => $branch->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/branches/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Sede no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/branches');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/branches', []);
        $response->assertStatus(401);
    }
}
