<?php

namespace Tests\Feature;

use App\Models\SubscriptionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionTypeTest extends TestCase
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
     * Test index - list all subscription types
     */
    public function test_index_returns_all_subscription_types(): void
    {
        // Crear tipos de suscripción
        SubscriptionType::create(['code' => 'MONTHLY', 'name' => 'Mensual', 'duration_days' => 30]);
        SubscriptionType::create(['code' => 'QUARTERLY', 'name' => 'Trimestral', 'duration_days' => 90]);
        SubscriptionType::create(['code' => 'ANNUAL', 'name' => 'Anual', 'duration_days' => 365]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/subscription-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'duration_days', 'created_at', 'updated_at'],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de tipos de suscripción',
                'code' => 200,
            ]);

        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /**
     * Test store - create new subscription type
     */
    public function test_store_creates_subscription_type_successfully(): void
    {
        $data = [
            'name' => 'Semestral',
            'code' => 'SEMIANNUAL',
            'duration_days' => 180,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscription-types', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code', 'duration_days', 'created_at', 'updated_at'],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de suscripción creado exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Semestral',
                    'code' => 'SEMIANNUAL',
                    'duration_days' => 180,
                ],
            ]);

        $this->assertDatabaseHas('subscription_types', [
            'name' => 'Semestral',
            'code' => 'SEMIANNUAL',
            'duration_days' => 180,
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscription-types', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => ['name', 'code', 'duration_days'],
            ]);
    }

    /**
     * Test store - unique code validation
     */
    public function test_store_fails_with_duplicate_code(): void
    {
        SubscriptionType::create(['code' => 'MONTHLY', 'name' => 'Test', 'duration_days' => 30]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscription-types', [
                'name' => 'Mensual',
                'code' => 'MONTHLY',
                'duration_days' => 30,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['code'],
            ]);
    }

    /**
     * Test store - duration_days validation
     */
    public function test_store_fails_with_invalid_duration_days(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscription-types', [
                'name' => 'Test',
                'code' => 'TEST',
                'duration_days' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['duration_days'],
            ]);
    }

    /**
     * Test show - get single subscription type
     */
    public function test_show_returns_subscription_type(): void
    {
        $subscriptionType = SubscriptionType::create([
            'code' => 'MONTHLY',
            'name' => 'Mensual',
            'duration_days' => 30,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/subscription-types/'.$subscriptionType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de suscripción encontrado',
                'code' => 200,
                'data' => [
                    'id' => $subscriptionType->id,
                    'name' => 'Mensual',
                    'code' => 'MONTHLY',
                    'duration_days' => 30,
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/subscription-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de suscripción no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update subscription type
     */
    public function test_update_modifies_subscription_type_successfully(): void
    {
        $subscriptionType = SubscriptionType::create([
            'code' => 'QUARTERLY',
            'name' => 'Trimestral',
            'duration_days' => 90,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/subscription-types/'.$subscriptionType->id, [
                'name' => 'Trimestral Actualizado',
                'code' => 'QUARTERLY',
                'duration_days' => 92,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de suscripción actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Trimestral Actualizado',
                    'code' => 'QUARTERLY',
                    'duration_days' => 92,
                ],
            ]);

        $this->assertDatabaseHas('subscription_types', [
            'id' => $subscriptionType->id,
            'name' => 'Trimestral Actualizado',
            'duration_days' => 92,
        ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/subscription-types/999', [
                'name' => 'Test',
                'code' => 'TEST',
                'duration_days' => 30,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de suscripción no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete subscription type
     */
    public function test_destroy_deletes_subscription_type_successfully(): void
    {
        $subscriptionType = SubscriptionType::create([
            'name' => 'Semanal',
            'code' => 'WEEKLY',
            'duration_days' => 7,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/subscription-types/'.$subscriptionType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de suscripción eliminado exitosamente',
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('subscription_types', [
            'id' => $subscriptionType->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/subscription-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de suscripción no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/subscription-types');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/subscription-types', []);
        $response->assertStatus(401);
    }
}
