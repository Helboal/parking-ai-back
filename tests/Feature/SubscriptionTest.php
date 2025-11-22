<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\DocumentType;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected $customer;

    protected $branch;

    protected $vehicleType;

    protected $subscriptionType;

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

        // Crear tipo de suscripción
        $this->subscriptionType = SubscriptionType::firstOrCreate(
            ['code' => 'MONTHLY'],
            ['name' => 'Mensual', 'duration_days' => 30]
        );

        // Crear cliente
        $this->customer = Customer::factory()->create([
            'document_type_id' => $documentType->id,
        ]);

        // Crear sede
        $this->branch = Branch::factory()->create();

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test index - list all subscriptions
     */
    public function test_index_returns_all_subscriptions(): void
    {
        // Crear suscripciones
        Subscription::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/subscriptions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'start_date',
                        'end_date',
                        'amount',
                        'is_active',
                        'customer_id',
                        'branch_id',
                        'vehicle_type_id',
                        'subscription_type_id',
                        'created_at',
                        'updated_at',
                        'customer' => [
                            'id',
                            'first_name',
                            'last_name',
                        ],
                        'branch' => [
                            'id',
                            'name',
                        ],
                        'vehicle_type' => [
                            'id',
                            'name',
                            'code',
                        ],
                        'subscription_type' => [
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
                'message' => 'Listado de suscripciones',
                'code' => 200,
            ]);

        // Verificar que hay 3 suscripciones
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test store - create new subscription
     */
    public function test_store_creates_subscription_successfully(): void
    {
        $data = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'amount' => 250000.00,
            'is_active' => true,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscriptions', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'start_date',
                    'end_date',
                    'amount',
                    'is_active',
                    'customer_id',
                    'branch_id',
                    'vehicle_type_id',
                    'subscription_type_id',
                    'created_at',
                    'updated_at',
                    'customer',
                    'branch',
                    'vehicle_type',
                    'subscription_type',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Suscripción creada exitosamente',
                'code' => 201,
                'data' => [
                    'amount' => '250000.00',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 250000.00,
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscriptions', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'start_date',
                    'end_date',
                    'amount',
                    'customer_id',
                    'branch_id',
                    'vehicle_type_id',
                    'subscription_type_id',
                ],
            ]);
    }

    /**
     * Test store - end_date before start_date
     */
    public function test_store_fails_with_end_date_before_start_date(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscriptions', [
                'start_date' => '2025-12-31',
                'end_date' => '2025-01-01',
                'amount' => 250000,
                'customer_id' => $this->customer->id,
                'branch_id' => $this->branch->id,
                'vehicle_type_id' => $this->vehicleType->id,
                'subscription_type_id' => $this->subscriptionType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['end_date'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test store - negative amount
     */
    public function test_store_fails_with_negative_amount(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/subscriptions', [
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
                'amount' => -1000,
                'customer_id' => $this->customer->id,
                'branch_id' => $this->branch->id,
                'vehicle_type_id' => $this->vehicleType->id,
                'subscription_type_id' => $this->subscriptionType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['amount'],
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
            ->postJson('/api/admin/subscriptions', [
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
                'amount' => 250000,
                'customer_id' => 99999,
                'branch_id' => 99999,
                'vehicle_type_id' => 99999,
                'subscription_type_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    'customer_id',
                    'branch_id',
                    'vehicle_type_id',
                    'subscription_type_id',
                ],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test show - get single subscription
     */
    public function test_show_returns_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'amount' => 500000.00,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/subscriptions/'.$subscription->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Suscripción encontrada',
                'code' => 200,
                'data' => [
                    'id' => $subscription->id,
                    'amount' => '500000.00',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'customer',
                    'branch',
                    'vehicle_type',
                    'subscription_type',
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/subscriptions/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Suscripción no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update subscription
     */
    public function test_update_modifies_subscription_successfully(): void
    {
        $subscription = Subscription::factory()->create([
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'amount' => 250000,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/subscriptions/'.$subscription->id, [
                'start_date' => '2025-02-01',
                'end_date' => '2025-02-28',
                'amount' => 300000,
                'is_active' => false,
                'customer_id' => $this->customer->id,
                'branch_id' => $this->branch->id,
                'vehicle_type_id' => $this->vehicleType->id,
                'subscription_type_id' => $this->subscriptionType->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Suscripción actualizada exitosamente',
                'code' => 200,
                'data' => [
                    'amount' => '300000.00',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'amount' => 300000.00,
            'is_active' => false,
        ]);
    }

    /**
     * Test update - validation errors
     */
    public function test_update_fails_with_validation_errors(): void
    {
        $subscription = Subscription::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/subscriptions/'.$subscription->id, [
                'start_date' => 'invalid-date',
                'end_date' => '2025-01-01',
                'amount' => 'not-a-number',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'start_date',
                    'amount',
                    'customer_id',
                    'branch_id',
                    'vehicle_type_id',
                    'subscription_type_id',
                ],
            ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/subscriptions/999', [
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
                'amount' => 250000,
                'customer_id' => $this->customer->id,
                'branch_id' => $this->branch->id,
                'vehicle_type_id' => $this->vehicleType->id,
                'subscription_type_id' => $this->subscriptionType->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Suscripción no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete subscription
     */
    public function test_destroy_deletes_subscription_successfully(): void
    {
        $subscription = Subscription::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/subscriptions/'.$subscription->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Suscripción eliminada exitosamente',
                'code' => 200,
            ]);

        // Verificar que fue eliminada
        $this->assertDatabaseMissing('subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/subscriptions/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Suscripción no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/subscriptions');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/subscriptions', []);
        $response->assertStatus(401);

        $subscription = Subscription::factory()->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        $response = $this->getJson('/api/admin/subscriptions/'.$subscription->id);
        $response->assertStatus(401);

        $response = $this->putJson('/api/admin/subscriptions/'.$subscription->id, []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/admin/subscriptions/'.$subscription->id);
        $response->assertStatus(401);
    }

    /**
     * Test cascade delete - deleting customer deletes subscriptions
     */
    public function test_deleting_customer_deletes_subscriptions(): void
    {
        // Crear un nuevo cliente con suscripciones
        $customer = Customer::factory()->create();

        $subscription1 = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        $subscription2 = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'subscription_type_id' => $this->subscriptionType->id,
        ]);

        // Verificar que existen
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription1->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription2->id]);

        // Eliminar el cliente
        $customer->delete();

        // Verificar que las suscripciones fueron eliminadas en cascada
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription1->id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription2->id]);
    }
}
