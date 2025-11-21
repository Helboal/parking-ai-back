<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear tipo de documento
        $this->documentType = DocumentType::create(['name' => 'Cédula de Ciudadanía', 'code' => 'CC']);

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test index - list all customers
     */
    public function test_index_returns_all_customers(): void
    {
        // Crear clientes
        Customer::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'document_number',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'mobile',
                        'address',
                        'birth_date',
                        'photo_url',
                        'notes',
                        'created_at',
                        'updated_at',
                        'document_type' => [
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
                'message' => 'Listado de clientes',
                'code' => 200,
            ]);

        // Verificar que hay 3 clientes
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test store - create new customer
     */
    public function test_store_creates_customer_successfully(): void
    {
        $data = [
            'document_number' => '1234567890',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'phone' => '6012345678',
            'mobile' => '3001234567',
            'address' => 'Calle 123 #45-67',
            'birth_date' => '1990-01-15',
            'photo_url' => 'https://example.com/photo.jpg',
            'notes' => 'Cliente frecuente',
            'document_type_id' => $this->documentType->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/customers', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'document_number',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'mobile',
                    'address',
                    'birth_date',
                    'photo_url',
                    'notes',
                    'created_at',
                    'updated_at',
                    'document_type',
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'code' => 201,
                'data' => [
                    'document_number' => '1234567890',
                    'first_name' => 'Juan',
                    'last_name' => 'Pérez',
                    'email' => 'juan.perez@example.com',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'document_number' => '1234567890',
            'email' => 'juan.perez@example.com',
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/customers', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
                'code' => 422,
            ])
            ->assertJsonStructure([
                'errors' => [
                    'document_number',
                    'first_name',
                    'last_name',
                    'document_type_id',
                ],
            ]);
    }

    /**
     * Test store - duplicate document number for same document type
     */
    public function test_store_fails_with_duplicate_document(): void
    {
        $existingCustomer = Customer::factory()->create([
            'document_type_id' => $this->documentType->id,
            'document_number' => '1234567890',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/customers', [
                'document_number' => '1234567890',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'juan.perez@example.com',
                'document_type_id' => $this->documentType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['document_number'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Errores de validación',
            ]);
    }

    /**
     * Test store - allows same document_number with different document_type
     */
    public function test_store_allows_same_document_number_with_different_type(): void
    {
        // Crear un segundo tipo de documento
        $secondDocType = DocumentType::firstOrCreate(
            ['code' => 'NIT'],
            ['name' => 'NIT']
        );

        // Crear cliente con documento CC 123456
        $existingCustomer = Customer::factory()->create([
            'document_type_id' => $this->documentType->id, // CC
            'document_number' => '123456',
        ]);

        // Intentar crear otro cliente con el MISMO número pero tipo NIT
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/customers', [
                'document_number' => '123456', // Mismo número
                'first_name' => 'María',
                'last_name' => 'González',
                'email' => 'maria.gonzalez@example.com',
                'document_type_id' => $secondDocType->id, // Diferente tipo
            ]);

        // Debe permitirlo porque el índice es compuesto
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
            ]);

        // Verificar que hay 2 clientes con el mismo document_number pero diferentes tipos
        $this->assertEquals(2, Customer::where('document_number', '123456')->count());
    }

    /**
     * Test store - duplicate email
     */
    public function test_store_fails_with_duplicate_email(): void
    {
        $existingCustomer = Customer::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/customers', [
                'document_number' => '9876543210',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'existing@example.com',
                'document_type_id' => $this->documentType->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['email'],
            ]);
    }

    /**
     * Test show - get single customer
     */
    public function test_show_returns_customer(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'test.customer@example.com',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/customers/'.$customer->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cliente encontrado',
                'code' => 200,
                'data' => [
                    'id' => $customer->id,
                    'email' => 'test.customer@example.com',
                    'first_name' => 'Test',
                    'last_name' => 'Customer',
                ],
            ])
            ->assertJsonStructure([
                'data' => ['document_type'],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/customers/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update customer
     */
    public function test_update_modifies_customer_successfully(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/customers/'.$customer->id, [
                'document_number' => $customer->document_number,
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => 'updated@example.com',
                'phone' => '6011112222',
                'mobile' => '3001112222',
                'document_type_id' => $customer->document_type_id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'first_name' => 'Updated',
                    'email' => 'updated@example.com',
                    'phone' => '6011112222',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Updated',
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * Test update - duplicate document number
     */
    public function test_update_fails_with_duplicate_document(): void
    {
        $customer1 = Customer::factory()->create([
            'document_type_id' => $this->documentType->id,
            'document_number' => '1111111111',
        ]);

        $customer2 = Customer::factory()->create([
            'document_type_id' => $this->documentType->id,
            'document_number' => '2222222222',
        ]);

        // Intentar actualizar customer2 con el documento de customer1
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/customers/'.$customer2->id, [
                'document_number' => '1111111111', // Documento de customer1
                'first_name' => 'Test',
                'last_name' => 'User',
                'document_type_id' => $this->documentType->id, // Mismo tipo
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['document_number'],
            ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/customers/999', [
                'document_number' => '1234567890',
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'document_type_id' => $this->documentType->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete customer
     */
    public function test_destroy_deletes_customer_successfully(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/customers/'.$customer->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cliente eliminado exitosamente',
                'code' => 200,
            ]);

        // Verificar que fue eliminado
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/customers/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/customers');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/customers', []);
        $response->assertStatus(401);

        $customer = Customer::factory()->create();

        $response = $this->getJson('/api/admin/customers/'.$customer->id);
        $response->assertStatus(401);

        $response = $this->putJson('/api/admin/customers/'.$customer->id, []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/admin/customers/'.$customer->id);
        $response->assertStatus(401);
    }
}
