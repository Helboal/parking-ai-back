<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function test_index_returns_all_invoices()
    {
        Invoice::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/admin/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'rate_per_minute',
                        'subtotal',
                        'total',
                        'entry_id',
                    ],
                ],
            ]);
    }

    /** @test */
    public function test_show_returns_invoice_with_payment_info()
    {
        $invoice = Invoice::factory()->create([
            'total' => 10000,
        ]);

        // Crear un pago parcial
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 5000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/admin/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $invoice->id,
                    'total' => 10000.0,
                    'total_paid' => 5000.0,
                    'balance_due' => 5000.0,
                    'is_paid' => false,
                ],
            ]);
    }

    /** @test */
    public function test_pending_returns_only_unpaid_invoices()
    {
        // Factura completamente pagada
        $paidInvoice = Invoice::factory()->create(['total' => 10000]);
        Payment::factory()->create([
            'invoice_id' => $paidInvoice->id,
            'amount' => 10000,
            'status' => 'completed',
        ]);

        // Factura parcialmente pagada
        $partialInvoice = Invoice::factory()->create(['total' => 20000]);
        Payment::factory()->create([
            'invoice_id' => $partialInvoice->id,
            'amount' => 10000,
            'status' => 'completed',
        ]);

        // Factura sin pagar
        $unpaidInvoice = Invoice::factory()->create(['total' => 15000]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/admin/invoices/pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Solo las 2 pendientes
            ->assertJson([
                'success' => true,
                'message' => '2 facturas pendientes de pago',
            ]);
    }

    /** @test */
    public function test_endpoints_require_authentication()
    {
        $response = $this->getJson('/api/admin/invoices');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/invoices/pending');
        $response->assertStatus(401);
    }
}
