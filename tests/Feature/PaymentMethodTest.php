<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
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
     * Test index - list all payment methods
     */
    public function test_index_returns_all_payment_methods(): void
    {
        // Crear métodos de pago
        PaymentMethod::create(['code' => 'CASH', 'name' => 'Efectivo', 'is_active' => true]);
        PaymentMethod::create(['code' => 'CREDIT_CARD', 'name' => 'Tarjeta de Crédito', 'is_active' => true]);
        PaymentMethod::create(['code' => 'DEBIT_CARD', 'name' => 'Tarjeta de Débito', 'is_active' => false]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'is_active', 'created_at', 'updated_at'],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de métodos de pago',
                'code' => 200,
            ]);

        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    /**
     * Test store - create new payment method
     */
    public function test_store_creates_payment_method_successfully(): void
    {
        $data = [
            'name' => 'Transferencia Bancaria',
            'code' => 'BANK_TRANSFER',
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/payment-methods', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code', 'is_active', 'created_at', 'updated_at'],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Método de pago creado exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Transferencia Bancaria',
                    'code' => 'BANK_TRANSFER',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('payment_methods', [
            'name' => 'Transferencia Bancaria',
            'code' => 'BANK_TRANSFER',
            'is_active' => true,
        ]);
    }

    /**
     * Test store - create payment method with default is_active
     */
    public function test_store_creates_payment_method_with_default_is_active(): void
    {
        $data = [
            'name' => 'PayPal',
            'code' => 'PAYPAL',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/payment-methods', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('payment_methods', [
            'code' => 'PAYPAL',
            'is_active' => true,
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/payment-methods', []);

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
        PaymentMethod::create(['code' => 'CASH', 'name' => 'Test', 'is_active' => true]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/payment-methods', [
                'name' => 'Efectivo',
                'code' => 'CASH',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['code'],
            ]);
    }

    /**
     * Test show - get single payment method
     */
    public function test_show_returns_payment_method(): void
    {
        $paymentMethod = PaymentMethod::create([
            'code' => 'CASH',
            'name' => 'Efectivo',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/payment-methods/'.$paymentMethod->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Método de pago encontrado',
                'code' => 200,
                'data' => [
                    'id' => $paymentMethod->id,
                    'name' => 'Efectivo',
                    'code' => 'CASH',
                    'is_active' => true,
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/payment-methods/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Método de pago no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update payment method
     */
    public function test_update_modifies_payment_method_successfully(): void
    {
        $paymentMethod = PaymentMethod::create([
            'code' => 'CASH',
            'name' => 'Efectivo',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/payment-methods/'.$paymentMethod->id, [
                'name' => 'Efectivo Actualizado',
                'code' => 'CASH',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Método de pago actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Efectivo Actualizado',
                    'code' => 'CASH',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'Efectivo Actualizado',
            'is_active' => false,
        ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/payment-methods/999', [
                'name' => 'Test',
                'code' => 'TEST',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Método de pago no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete payment method
     */
    public function test_destroy_deletes_payment_method_successfully(): void
    {
        $paymentMethod = PaymentMethod::create([
            'name' => 'Crypto',
            'code' => 'CRYPTO',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/payment-methods/'.$paymentMethod->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Método de pago eliminado exitosamente',
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/payment-methods/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Método de pago no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/payment-methods');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/payment-methods', []);
        $response->assertStatus(401);
    }
}
