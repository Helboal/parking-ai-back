<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected PaymentMethod $paymentMethod;

    protected Invoice $invoice;

    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear documento tipo necesario para usuarios
        DocumentType::firstOrCreate(
            ['code' => 'CC'],
            ['name' => 'Cédula de Ciudadanía']
        );

        // Crear usuario para autenticación
        $this->user = User::factory()->create();

        // Crear método de pago
        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['code' => 'CASH'],
            ['name' => 'Efectivo', 'is_active' => true]
        );

        // Crear una factura y una suscripción para las pruebas
        $this->invoice = Invoice::factory()->create();
        $this->subscription = Subscription::factory()->create();
    }

    public function test_index_returns_all_payments(): void
    {
        Payment::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'payment_datetime',
                        'status',
                        'reference_number',
                        'invoice_id',
                        'subscription_id',
                        'payment_method_id',
                        'user_id',
                        'notes',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Pagos obtenidos exitosamente',
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_store_creates_payment_successfully(): void
    {
        $paymentData = [
            'amount' => 50000.00,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'completed',
            'reference_number' => 'TRX-123456',
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
            'user_id' => $this->user->id,
            'notes' => 'Pago en efectivo',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', $paymentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pago creado exitosamente',
                'data' => [
                    'amount' => '50000.00',
                    'status' => 'completed',
                    'reference_number' => 'TRX-123456',
                    'invoice_id' => $this->invoice->id,
                    'payment_method_id' => $this->paymentMethod->id,
                    'user_id' => $this->user->id,
                    'notes' => 'Pago en efectivo',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 50000.00,
            'status' => 'completed',
            'invoice_id' => $this->invoice->id,
        ]);
    }

    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ])
            ->assertJsonStructure([
                'errors' => [
                    'amount',
                    'payment_datetime',
                    'status',
                    'payment_method_id',
                ],
            ]);
    }

    public function test_store_fails_with_negative_amount(): void
    {
        $paymentData = [
            'amount' => -1000,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'completed',
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', $paymentData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ])
            ->assertJsonStructure([
                'errors' => ['amount'],
            ]);
    }

    public function test_store_fails_with_invalid_status(): void
    {
        $paymentData = [
            'amount' => 50000.00,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'invalid_status',
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', $paymentData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ])
            ->assertJsonStructure([
                'errors' => ['status'],
            ]);
    }

    public function test_store_fails_without_invoice_or_subscription(): void
    {
        $paymentData = [
            'amount' => 50000.00,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'completed',
            'payment_method_id' => $this->paymentMethod->id,
            'user_id' => $this->user->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', $paymentData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ])
            ->assertJsonStructure([
                'errors' => ['invoice_or_subscription'],
            ]);
    }

    public function test_store_fails_with_invalid_foreign_keys(): void
    {
        $paymentData = [
            'amount' => 50000.00,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'completed',
            'invoice_id' => 99999, // ID inexistente
            'payment_method_id' => 99999, // ID inexistente
            'user_id' => 99999, // ID inexistente
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', $paymentData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ])
            ->assertJsonStructure([
                'errors' => [
                    'invoice_id',
                    'payment_method_id',
                    'user_id',
                ],
            ]);
    }

    public function test_show_returns_payment(): void
    {
        $payment = Payment::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pago obtenido exitosamente',
                'data' => [
                    'id' => $payment->id,
                    'amount' => (string) $payment->amount,
                    'status' => $payment->status,
                ],
            ]);
    }

    public function test_show_fails_with_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/payments/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pago no encontrado',
            ]);
    }

    public function test_update_modifies_payment_successfully(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 30000.00,
            'status' => 'pending',
        ]);

        $updateData = [
            'amount' => 50000.00,
            'payment_datetime' => '2024-01-16 10:00:00',
            'status' => 'completed',
            'invoice_id' => $payment->invoice_id ?? $this->invoice->id,
            'subscription_id' => $payment->subscription_id,
            'payment_method_id' => $this->paymentMethod->id,
            'user_id' => $this->user->id,
            'notes' => 'Pago actualizado',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/payments/{$payment->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pago actualizado exitosamente',
                'data' => [
                    'id' => $payment->id,
                    'amount' => '50000.00',
                    'status' => 'completed',
                    'notes' => 'Pago actualizado',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount' => 50000.00,
            'status' => 'completed',
        ]);
    }

    public function test_update_fails_with_validation_errors(): void
    {
        $payment = Payment::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/payments/{$payment->id}", [
                'amount' => 'invalid',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    public function test_update_fails_with_not_found(): void
    {
        $updateData = [
            'amount' => 50000.00,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'completed',
            'invoice_id' => $this->invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/admin/payments/99999', $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pago no encontrado',
            ]);
    }

    public function test_destroy_deletes_payment_successfully(): void
    {
        $payment = Payment::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pago eliminado exitosamente',
            ]);

        $this->assertDatabaseMissing('payments', [
            'id' => $payment->id,
        ]);
    }

    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/admin/payments/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Pago no encontrado',
            ]);
    }

    public function test_endpoints_require_authentication(): void
    {
        $payment = Payment::factory()->create();

        // Test index sin autenticación
        $response = $this->getJson('/api/admin/payments');
        $response->assertStatus(401);

        // Test store sin autenticación
        $response = $this->postJson('/api/admin/payments', []);
        $response->assertStatus(401);

        // Test show sin autenticación
        $response = $this->getJson("/api/admin/payments/{$payment->id}");
        $response->assertStatus(401);

        // Test update sin autenticación
        $response = $this->putJson("/api/admin/payments/{$payment->id}", []);
        $response->assertStatus(401);

        // Test destroy sin autenticación
        $response = $this->deleteJson("/api/admin/payments/{$payment->id}");
        $response->assertStatus(401);
    }

    public function test_payment_can_be_for_invoice_or_subscription(): void
    {
        // Test pago para factura
        $invoicePayment = [
            'amount' => 30000.00,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'completed',
            'invoice_id' => $this->invoice->id,
            'subscription_id' => null,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', $invoicePayment);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'subscription_id' => null,
        ]);

        // Test pago para suscripción
        $subscriptionPayment = [
            'amount' => 100000.00,
            'payment_datetime' => '2024-01-15 14:30:00',
            'status' => 'completed',
            'invoice_id' => null,
            'subscription_id' => $this->subscription->id,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/payments', $subscriptionPayment);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => null,
            'subscription_id' => $this->subscription->id,
        ]);
    }
}
