<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol Super Administrador para los tests
        Role::create(['name' => 'Super Administrador', 'guard_name' => 'sanctum']);
    }

    /**
     * Test login with valid credentials
     */
    public function test_login_with_valid_credentials(): void
    {
        // Crear usuario y asignar rol
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('Super Administrador');

        // Hacer login
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                        'role' => [
                            'id',
                            'name',
                            'guard_name',
                            'created_at',
                            'updated_at',
                            'permissions',
                        ],
                    ],
                    'token',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login exitoso',
                'code' => 200,
                'data' => [
                    'user' => [
                        'role' => [
                            'name' => 'Super Administrador',
                            'guard_name' => 'sanctum',
                        ],
                    ],
                ],
            ]);

        // Verificar que el token existe
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_with_invalid_credentials(): void
    {
        // Crear usuario
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Intentar login con contraseña incorrecta
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Verificar respuesta
        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'code',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Credenciales incorrectas',
                'data' => null,
                'code' => 401,
            ]);
    }

    /**
     * Test login with non-existent user
     */
    public function test_login_with_non_existent_user(): void
    {
        // Intentar login con usuario que no existe
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Verificar respuesta
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciales incorrectas',
                'data' => null,
                'code' => 401,
            ]);
    }

    /**
     * Test login with validation errors
     */
    public function test_login_with_validation_errors(): void
    {
        // Intentar login sin datos
        $response = $this->postJson('/api/login', []);

        // Verificar respuesta
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'data' => null,
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'email',
                    'password',
                ],
            ]);
    }

    /**
     * Test logout with valid token
     */
    public function test_logout_with_valid_token(): void
    {
        // Crear usuario y generar token
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        // Hacer logout
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout exitoso',
                'data' => null,
                'code' => 200,
            ]);

        // Verificar que el usuario ya no tiene tokens
        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test logout without token
     */
    public function test_logout_without_token(): void
    {
        // Intentar logout sin token
        $response = $this->postJson('/api/logout');

        // Verificar respuesta
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'No autenticado',
                'data' => null,
                'code' => 401,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'code',
            ]);
    }

    /**
     * Test get authenticated user with valid token
     */
    public function test_get_authenticated_user_with_valid_token(): void
    {
        // Crear usuario, asignar rol y generar token
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user->assignRole('Super Administrador');
        $token = $user->createToken('api-token')->plainTextToken;

        // Obtener usuario autenticado
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'role' => [
                        'id',
                        'name',
                        'guard_name',
                        'created_at',
                        'updated_at',
                        'permissions',
                    ],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Usuario autenticado',
                'code' => 200,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'role' => [
                        'name' => 'Super Administrador',
                        'guard_name' => 'sanctum',
                    ],
                ],
            ]);
    }

    /**
     * Test get authenticated user without token
     */
    public function test_get_authenticated_user_without_token(): void
    {
        // Intentar obtener usuario sin token
        $response = $this->getJson('/api/user');

        // Verificar respuesta
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'No autenticado',
                'data' => null,
                'code' => 401,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'errors',
                'code',
            ]);
    }
}
