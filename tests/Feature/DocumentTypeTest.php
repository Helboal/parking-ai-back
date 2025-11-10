<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentTypeTest extends TestCase
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
     * Test index - list all document types
     */
    public function test_index_returns_all_document_types(): void
    {
        // Crear tipos de documento
        DocumentType::create(['name' => 'Cédula de Ciudadanía', 'code' => 'CC']);
        DocumentType::create(['name' => 'Pasaporte', 'code' => 'PP']);
        DocumentType::create(['name' => 'NIT', 'code' => 'NIT']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/document-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'created_at', 'updated_at']
                ],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Listado de tipos de documento',
                'code' => 200,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test store - create new document type
     */
    public function test_store_creates_document_type_successfully(): void
    {
        $data = [
            'name' => 'Cédula de Ciudadanía',
            'code' => 'CC',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/admin/document-types', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code', 'created_at', 'updated_at'],
                'code',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de documento creado exitosamente',
                'code' => 201,
                'data' => [
                    'name' => 'Cédula de Ciudadanía',
                    'code' => 'CC',
                ],
            ]);

        $this->assertDatabaseHas('document_types', [
            'name' => 'Cédula de Ciudadanía',
            'code' => 'CC',
        ]);
    }

    /**
     * Test store - validation errors
     */
    public function test_store_fails_with_validation_errors(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/admin/document-types', []);

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
        DocumentType::create(['name' => 'Test', 'code' => 'CC']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/admin/document-types', [
                'name' => 'Cédula de Ciudadanía',
                'code' => 'CC',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => ['code'],
            ]);
    }

    /**
     * Test show - get single document type
     */
    public function test_show_returns_document_type(): void
    {
        $documentType = DocumentType::create([
            'name' => 'Cédula de Ciudadanía',
            'code' => 'CC',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/document-types/' . $documentType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de documento encontrado',
                'code' => 200,
                'data' => [
                    'id' => $documentType->id,
                    'name' => 'Cédula de Ciudadanía',
                    'code' => 'CC',
                ],
            ]);
    }

    /**
     * Test show - not found
     */
    public function test_show_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/document-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de documento no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test update - update document type
     */
    public function test_update_modifies_document_type_successfully(): void
    {
        $documentType = DocumentType::create([
            'name' => 'Cédula de Ciudadanía',
            'code' => 'CC',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/admin/document-types/' . $documentType->id, [
                'name' => 'Cédula de Ciudadanía Actualizada',
                'code' => 'CC',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de documento actualizado exitosamente',
                'code' => 200,
                'data' => [
                    'name' => 'Cédula de Ciudadanía Actualizada',
                    'code' => 'CC',
                ],
            ]);

        $this->assertDatabaseHas('document_types', [
            'id' => $documentType->id,
            'name' => 'Cédula de Ciudadanía Actualizada',
        ]);
    }

    /**
     * Test update - not found
     */
    public function test_update_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/admin/document-types/999', [
                'name' => 'Test',
                'code' => 'TEST',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de documento no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test destroy - delete document type
     */
    public function test_destroy_deletes_document_type_successfully(): void
    {
        $documentType = DocumentType::create([
            'name' => 'Cédula de Ciudadanía',
            'code' => 'CC',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/admin/document-types/' . $documentType->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de documento eliminado exitosamente',
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('document_types', [
            'id' => $documentType->id,
        ]);
    }

    /**
     * Test destroy - not found
     */
    public function test_destroy_fails_with_not_found(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/admin/document-types/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tipo de documento no encontrado',
                'code' => 404,
            ]);
    }

    /**
     * Test unauthorized access
     */
    public function test_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/document-types');
        $response->assertStatus(401);

        $response = $this->postJson('/api/admin/document-types', []);
        $response->assertStatus(401);
    }
}
