<?php

namespace Tests\Feature;

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

    protected $user;

    protected $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['code' => 'CASH'],
            ['name' => 'Efectivo', 'is_active' => true]
        );
    }

    /** @test */
    public function test_store_creates_payment_for_invoice()
    {
        $invoice = Invoice::factory()->create(['total' => 10000]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/payments', [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => 10000.0,
                    'invoice_id' => $invoice->id,
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function test_store_creates_payment_for_subscription()
    {
        $subscription = Subscription::factory()->create([
            'amount' => 50000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/payments', [
            'subscription_id' => $subscription->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => 50000.0,
                    'subscription_id' => $subscription->id,
                    'status' => 'completed',
                ],
            ]);
    }

    /** @test */
    public function test_store_fails_when_invoice_already_paid()
    {
        $invoice = Invoice::factory()->create(['total' => 10000]);

        // Crear pago completo
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/payments', [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'La factura ya está pagada',
            ]);
    }

    /** @test */
    public function test_store_creates_partial_payment()
    {
        $invoice = Invoice::factory()->create(['total' => 10000]);

        // Pago parcial previo
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 6000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/payments', [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => 4000.0, // Balance pendiente
                    'invoice_id' => $invoice->id,
                ],
            ]);
    }

    /** @test */
    public function test_store_fails_without_invoice_or_subscription()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/payments', [
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function test_store_fails_with_both_invoice_and_subscription()
    {
        $invoice = Invoice::factory()->create();
        $subscription = Subscription::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/payments', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $subscription->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function test_store_fails_for_inactive_subscription()
    {
        $subscription = Subscription::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/admin/payments', [
            'subscription_id' => $subscription->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'La suscripción no está activa',
            ]);
    }

    /** @test */
    public function test_index_returns_all_payments()
    {
        Payment::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/admin/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'status',
                        'payment_datetime',
                    ],
                ],
            ]);
    }

    /** @test */
    public function test_show_returns_payment()
    {
        $payment = Payment::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/admin/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                ],
            ]);
    }

    /** @test */
    public function test_endpoints_require_authentication()
    {
        $response = $this->getJson('/api/admin/payments');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/payments', []);
        $response->assertStatus(401);
    }
}
