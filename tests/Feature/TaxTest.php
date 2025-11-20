<?php

namespace Tests\Feature;

use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_taxes(): void
    {
        Tax::create(['name' => 'IVA', 'code' => 'VAT', 'percentage' => 19.00, 'is_active' => true]);
        Tax::create(['name' => 'ICA', 'code' => 'ICA', 'percentage' => 0.50, 'is_active' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/admin/taxes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'percentage', 'is_active', 'created_at', 'updated_at'],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_tax(): void
    {
        $taxData = [
            'name' => 'IVA',
            'code' => 'VAT',
            'percentage' => 19.00,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/taxes', $taxData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code', 'percentage', 'is_active', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('taxes', ['code' => 'VAT', 'percentage' => 19.00]);
    }

    public function test_can_show_tax(): void
    {
        $tax = Tax::create(['name' => 'IVA', 'code' => 'VAT', 'percentage' => 19.00, 'is_active' => true]);

        $response = $this->actingAs($this->user)->getJson("/api/admin/taxes/{$tax->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code', 'percentage', 'is_active', 'created_at', 'updated_at'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $tax->id,
                    'code' => 'VAT',
                ],
            ]);
    }

    public function test_can_update_tax(): void
    {
        $tax = Tax::create(['name' => 'IVA', 'code' => 'VAT', 'percentage' => 19.00, 'is_active' => true]);

        $updateData = [
            'name' => 'IVA Actualizado',
            'code' => 'VAT',
            'percentage' => 21.00,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/admin/taxes/{$tax->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'IVA Actualizado',
                    'percentage' => '21.00',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('taxes', ['id' => $tax->id, 'name' => 'IVA Actualizado', 'percentage' => 21.00]);
    }

    public function test_can_delete_tax(): void
    {
        $tax = Tax::create(['name' => 'IVA', 'code' => 'VAT', 'percentage' => 19.00, 'is_active' => true]);

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/taxes/{$tax->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('taxes', ['id' => $tax->id]);
    }

    public function test_cannot_create_tax_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/taxes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'percentage']);
    }

    public function test_cannot_create_tax_with_duplicate_code(): void
    {
        Tax::create(['name' => 'IVA', 'code' => 'VAT', 'percentage' => 19.00, 'is_active' => true]);

        $response = $this->actingAs($this->user)->postJson('/api/admin/taxes', [
            'name' => 'Otro Impuesto',
            'code' => 'VAT',
            'percentage' => 10.00,
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_cannot_show_nonexistent_tax(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/taxes/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_nonexistent_tax(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/admin/taxes/9999', [
            'name' => 'IVA',
            'code' => 'VAT',
            'percentage' => 19.00,
            'is_active' => true,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_nonexistent_tax(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/admin/taxes/9999');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_to_list_taxes(): void
    {
        $response = $this->getJson('/api/admin/taxes');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_tax(): void
    {
        $response = $this->postJson('/api/admin/taxes', [
            'name' => 'IVA',
            'code' => 'VAT',
            'percentage' => 19.00,
            'is_active' => true,
        ]);

        $response->assertStatus(401);
    }
}
