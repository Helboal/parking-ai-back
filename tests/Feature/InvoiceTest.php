<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\DocumentType;
use App\Models\Entry;
use App\Models\EntryType;
use App\Models\Invoice;
use App\Models\InvoiceTax;
use App\Models\Tax;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceTest extends TestCase
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
     * Test index - list all invoices with relationships
     */
    public function test_index_returns_all_invoices(): void
    {
        // Crear datos necesarios
        $entry1 = Entry::factory()->create();
        $entry2 = Entry::factory()->create();

        $invoice1 = Invoice::create([
            'rate_per_minute' => 100.00,
            'discount_percentage' => 10,
            'flat_rate_applied' => false,
            'subtotal' => 5000.00,
            'discount_amount' => 500.00,
            'tax_amount' => 855.00,
            'total' => 5355.00,
            'entry_id' => $entry1->id,
        ]);

        $invoice2 = Invoice::create([
            'rate_per_minute' => 150.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => true,
            'subtotal' => 8000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 1520.00,
            'total' => 9520.00,
            'entry_id' => $entry2->id,
        ]);

        // Crear impuestos asociados
        $tax = Tax::factory()->create();
        InvoiceTax::create([
            'invoice_id' => $invoice1->id,
            'tax_id' => $tax->id,
            'tax_percentage' => 19.00,
            'tax_amount' => 855.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'rate_per_minute',
                        'discount_percentage',
                        'flat_rate_applied',
                        'subtotal',
                        'discount_amount',
                        'tax_amount',
                        'total',
                        'entry_id',
                        'created_at',
                        'updated_at',
                        'entry',
                        'invoice_taxes',
                    ],
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de facturas',
                'code' => 200,
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    /**
     * Test store - create invoice successfully
     */
    public function test_store_creates_invoice_successfully(): void
    {
        $entry = Entry::factory()->create();

        $data = [
            'rate_per_minute' => 100.50,
            'discount_percentage' => 15,
            'flat_rate_applied' => false,
            'subtotal' => 10000.00,
            'discount_amount' => 1500.00,
            'tax_amount' => 1615.00,
            'total' => 10115.00,
            'entry_id' => $entry->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'rate_per_minute',
                    'discount_percentage',
                    'flat_rate_applied',
                    'subtotal',
                    'discount_amount',
                    'tax_amount',
                    'total',
                    'entry_id',
                    'created_at',
                    'updated_at',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Factura creada exitosamente',
                'code' => 201,
                'data' => [
                    'rate_per_minute' => '100.50',
                    'discount_percentage' => 15,
                    'flat_rate_applied' => false,
                    'subtotal' => '10000.00',
                    'entry_id' => $entry->id,
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'entry_id' => $entry->id,
            'rate_per_minute' => 100.50,
            'discount_percentage' => 15,
        ]);
    }

    /**
     * Test store - validation errors for required fields
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'rate_per_minute',
                    'subtotal',
                    'total',
                    'entry_id',
                ],
            ]);
    }

    /**
     * Test store - fails with negative values
     */
    public function test_store_fails_with_negative_values(): void
    {
        $entry = Entry::factory()->create();

        // Test negative rate_per_minute
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', [
                'rate_per_minute' => -100.00,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => 5000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 5000.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['rate_per_minute']]);

        // Test negative subtotal
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', [
                'rate_per_minute' => 100.00,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => -5000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 5000.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['subtotal']]);

        // Test negative total
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', [
                'rate_per_minute' => 100.00,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => 5000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => -5000.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['total']]);
    }

    /**
     * Test store - fails with invalid discount_percentage (must be 0-100)
     */
    public function test_store_fails_with_invalid_discount_percentage(): void
    {
        $entry = Entry::factory()->create();

        // Test discount_percentage > 100
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', [
                'rate_per_minute' => 100.00,
                'discount_percentage' => 150,
                'flat_rate_applied' => false,
                'subtotal' => 5000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 5000.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['discount_percentage']]);

        // Test discount_percentage < 0
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', [
                'rate_per_minute' => 100.00,
                'discount_percentage' => -10,
                'flat_rate_applied' => false,
                'subtotal' => 5000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 5000.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['discount_percentage']]);
    }

    /**
     * Test store - fails with duplicate entry_id (unique constraint)
     */
    public function test_store_fails_with_duplicate_entry_id(): void
    {
        $entry = Entry::factory()->create();

        // Crear primera factura
        Invoice::create([
            'rate_per_minute' => 100.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => false,
            'subtotal' => 5000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total' => 5000.00,
            'entry_id' => $entry->id,
        ]);

        // Intentar crear segunda factura con el mismo entry_id
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', [
                'rate_per_minute' => 150.00,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => 8000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 8000.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['entry_id']]);
    }

    /**
     * Test store - fails with invalid entry_id (must exist in entries table)
     */
    public function test_store_fails_with_invalid_entry_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/invoices', [
                'rate_per_minute' => 100.00,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => 5000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 5000.00,
                'entry_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['entry_id']]);
    }

    /**
     * Test show - get single invoice by ID
     */
    public function test_show_returns_invoice(): void
    {
        $entry = Entry::factory()->create();
        $invoice = Invoice::create([
            'rate_per_minute' => 120.00,
            'discount_percentage' => 10,
            'flat_rate_applied' => false,
            'subtotal' => 6000.00,
            'discount_amount' => 600.00,
            'tax_amount' => 1026.00,
            'total' => 6426.00,
            'entry_id' => $entry->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/invoices/'.$invoice->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Factura encontrada',
                'code' => 200,
                'data' => [
                    'id' => $invoice->id,
                    'rate_per_minute' => '120.00',
                    'discount_percentage' => 10,
                    'flat_rate_applied' => false,
                    'subtotal' => '6000.00',
                    'entry_id' => $entry->id,
                ],
            ]);
    }

    /**
     * Test show - fails with not found (404)
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/invoices/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Factura no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test update - modify invoice successfully
     */
    public function test_update_modifies_invoice_successfully(): void
    {
        $entry = Entry::factory()->create();
        $invoice = Invoice::create([
            'rate_per_minute' => 100.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => false,
            'subtotal' => 5000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 950.00,
            'total' => 5950.00,
            'entry_id' => $entry->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/invoices/'.$invoice->id, [
                'rate_per_minute' => 150.00,
                'discount_percentage' => 20,
                'flat_rate_applied' => true,
                'subtotal' => 8000.00,
                'discount_amount' => 1600.00,
                'tax_amount' => 1216.00,
                'total' => 7616.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Factura actualizada exitosamente',
                'code' => 200,
                'data' => [
                    'rate_per_minute' => '150.00',
                    'discount_percentage' => 20,
                    'flat_rate_applied' => true,
                    'subtotal' => '8000.00',
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'rate_per_minute' => 150.00,
            'discount_percentage' => 20,
            'flat_rate_applied' => true,
        ]);
    }

    /**
     * Test update - fails with validation errors
     */
    public function test_update_fails_with_validation_errors(): void
    {
        $entry = Entry::factory()->create();
        $invoice = Invoice::create([
            'rate_per_minute' => 100.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => false,
            'subtotal' => 5000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total' => 5000.00,
            'entry_id' => $entry->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/invoices/'.$invoice->id, [
                'rate_per_minute' => -100.00,
                'subtotal' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ]);
    }

    /**
     * Test update - fails with duplicate entry_id
     */
    public function test_update_fails_with_duplicate_entry_id(): void
    {
        $entry1 = Entry::factory()->create();
        $entry2 = Entry::factory()->create();

        $invoice1 = Invoice::create([
            'rate_per_minute' => 100.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => false,
            'subtotal' => 5000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total' => 5000.00,
            'entry_id' => $entry1->id,
        ]);

        $invoice2 = Invoice::create([
            'rate_per_minute' => 150.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => false,
            'subtotal' => 8000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total' => 8000.00,
            'entry_id' => $entry2->id,
        ]);

        // Intentar cambiar invoice2 para que use entry1.id (ya usado por invoice1)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/invoices/'.$invoice2->id, [
                'rate_per_minute' => 150.00,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => 8000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 8000.00,
                'entry_id' => $entry1->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['entry_id']]);
    }

    /**
     * Test update - fails with not found (404)
     */
    public function test_update_fails_with_not_found(): void
    {
        $entry = Entry::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/invoices/99999', [
                'rate_per_minute' => 100.00,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => 5000.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total' => 5000.00,
                'entry_id' => $entry->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Factura no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete invoice successfully
     */
    public function test_destroy_deletes_invoice_successfully(): void
    {
        $entry = Entry::factory()->create();
        $invoice = Invoice::create([
            'rate_per_minute' => 100.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => false,
            'subtotal' => 5000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total' => 5000.00,
            'entry_id' => $entry->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/invoices/'.$invoice->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Factura eliminada exitosamente',
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('invoices', [
            'id' => $invoice->id,
        ]);
    }

    /**
     * Test destroy - fails with not found (404)
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/invoices/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Factura no encontrada',
                'code' => 404,
            ]);
    }

    /**
     * Test endpoints require authentication (401)
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/invoices');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/invoices', []);
        $response->assertStatus(401);
    }

    /**
     * Test deleting invoice also deletes invoice_taxes (cascade delete)
     */
    public function test_deleting_invoice_deletes_invoice_taxes(): void
    {
        $entry = Entry::factory()->create();
        $invoice = Invoice::create([
            'rate_per_minute' => 100.00,
            'discount_percentage' => 0,
            'flat_rate_applied' => false,
            'subtotal' => 5000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 950.00,
            'total' => 5950.00,
            'entry_id' => $entry->id,
        ]);

        // Crear taxes asociados
        $tax1 = Tax::factory()->create();
        $tax2 = Tax::factory()->create();

        $invoiceTax1 = InvoiceTax::create([
            'invoice_id' => $invoice->id,
            'tax_id' => $tax1->id,
            'tax_percentage' => 19.00,
            'tax_amount' => 950.00,
        ]);

        $invoiceTax2 = InvoiceTax::create([
            'invoice_id' => $invoice->id,
            'tax_id' => $tax2->id,
            'tax_percentage' => 5.00,
            'tax_amount' => 250.00,
        ]);

        // Verificar que existen
        $this->assertDatabaseHas('invoice_taxes', ['id' => $invoiceTax1->id]);
        $this->assertDatabaseHas('invoice_taxes', ['id' => $invoiceTax2->id]);

        // Eliminar factura
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/invoices/'.$invoice->id);

        $response->assertStatus(200);

        // Verificar que los invoice_taxes fueron eliminados (cascade)
        $this->assertDatabaseMissing('invoice_taxes', ['id' => $invoiceTax1->id]);
        $this->assertDatabaseMissing('invoice_taxes', ['id' => $invoiceTax2->id]);
    }
}
