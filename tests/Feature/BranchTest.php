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
        Branch::create([
            'name' => 'Sede Norte',
            'code' => 'NORTE',
            'address' => 'Calle 100 # 15-20',
            'phone' => '3001234567',
            'email' => 'norte@parqueadero.com',
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_active' => true,
        ]);

        Branch::create([
            'name' => 'Sede Sur',
            'code' => 'SUR',
            'address' => 'Carrera 30 # 45-80',
            'phone' => '3009876543',
            'email' => 'sur@parqueadero.com',
            'opening_time' => '07:00',
            'closing_time' => '23:00',
            'is_active' => true,
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
                        'code',
                        'address',
                        'phone',
                        'email',
                        'opening_time',
                        'closing_time',
                        'is_active',
                        'created_at',
                        'updated_at',
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
            'code' => 'CENTRO',
            'address' => 'Avenida Jiménez # 7-35',
            'phone' => '3005555555',
            'email' => 'centro@parqueadero.com',
            'opening_time' => '05:00',
            'closing_time' => '20:00',
            'is_active' => true,
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
                    'code',
                    'address',
                    'phone',
                    'email',
                    'opening_time',
                    'closing_time',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Sede creada exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Sede Centro',
                    'code' => 'CENTRO',
                    'address' => 'Avenida Jiménez # 7-35',
                    'phone' => '3005555555',
                    'email' => 'centro@parqueadero.com',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'Sede Centro',
            'code' => 'CENTRO',
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
                    'code',
                    'address',
                ],
            ]);
    }

    /**
     * Test store - duplicate name
     */
    public function test_store_fails_with_duplicate_name(): void
    {
        Branch::create([
            'name' => 'Sede Duplicada',
            'code' => 'DUP1',
            'address' => 'Calle 1',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', [
                'name' => 'Sede Duplicada',
                'code' => 'DUP2',
                'address' => 'Calle 2',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['name'],
            ]);
    }

    /**
     * Test store - duplicate code
     */
    public function test_store_fails_with_duplicate_code(): void
    {
        Branch::create([
            'name' => 'Sede Primera',
            'code' => 'CODIGO',
            'address' => 'Calle 1',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', [
                'name' => 'Sede Segunda',
                'code' => 'CODIGO',
                'address' => 'Calle 2',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['code'],
            ]);
    }

    /**
     * Test store - invalid email format
     */
    public function test_store_fails_with_invalid_email(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', [
                'name' => 'Sede Test',
                'code' => 'TEST',
                'address' => 'Calle Test',
                'email' => 'invalid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['email'],
            ]);
    }

    /**
     * Test store - invalid time format
     */
    public function test_store_fails_with_invalid_time_format(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/branches', [
                'name' => 'Sede Test',
                'code' => 'TEST',
                'address' => 'Calle Test',
                'opening_time' => '25:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['opening_time'],
            ]);
    }

    /**
     * Test show - get single branch
     */
    public function test_show_returns_branch(): void
    {
        $branch = Branch::create([
            'name' => 'Sede Test',
            'code' => 'TEST',
            'address' => 'Calle Test # 10-20',
            'phone' => '3001111111',
            'email' => 'test@parqueadero.com',
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_active' => true,
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
                    'code' => 'TEST',
                    'address' => 'Calle Test # 10-20',
                    'email' => 'test@parqueadero.com',
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
            'code' => 'ORIG',
            'address' => 'Calle Original',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/branches/'.$branch->id, [
                'name' => 'Sede Actualizada',
                'code' => 'ACT',
                'address' => 'Calle Actualizada',
                'phone' => '3002222222',
                'email' => 'actualizada@parqueadero.com',
                'opening_time' => '07:00',
                'closing_time' => '21:00',
                'is_active' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sede actualizada exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Sede Actualizada',
                    'code' => 'ACT',
                    'address' => 'Calle Actualizada',
                    'email' => 'actualizada@parqueadero.com',
                ],
            ]);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Sede Actualizada',
            'code' => 'ACT',
        ]);
    }

    /**
     * Test update - duplicate code validation
     */
    public function test_update_fails_with_duplicate_code(): void
    {
        Branch::create([
            'name' => 'Sede Primera',
            'code' => 'PRIMERA',
            'address' => 'Calle 1',
        ]);

        $branch2 = Branch::create([
            'name' => 'Sede Segunda',
            'code' => 'SEGUNDA',
            'address' => 'Calle 2',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/branches/'.$branch2->id, [
                'name' => 'Sede Segunda',
                'code' => 'PRIMERA',
                'address' => 'Calle 2',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['code'],
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
                'code' => 'TEST',
                'address' => 'Test',
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
            'code' => 'ELIM',
            'address' => 'Calle Test',
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
