<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchTax;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTaxTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

    private Tax $tax;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $this->tax = Tax::create(['name' => 'IVA', 'code' => 'VAT', 'percentage' => 19.00, 'is_active' => true]);
    }

    public function test_can_list_branch_taxes(): void
    {
        BranchTax::create([
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-taxes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'branch_id', 'tax_id', 'branch', 'tax', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_branch_tax(): void
    {
        $branchTaxData = [
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-taxes', $branchTaxData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'branch_id', 'tax_id', 'branch', 'tax'],
            ]);

        $this->assertDatabaseHas('branch_taxes', [
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);
    }

    public function test_cannot_create_duplicate_branch_tax(): void
    {
        BranchTax::create([
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);

        $branchTaxData = [
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-taxes', $branchTaxData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    public function test_can_show_branch_tax(): void
    {
        $branchTax = BranchTax::create([
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/admin/branch-taxes/{$branchTax->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'branch_id', 'tax_id', 'branch', 'tax'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $branchTax->id,
                ],
            ]);
    }

    public function test_can_update_branch_tax(): void
    {
        $branchTax = BranchTax::create([
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);

        $newTax = Tax::create(['name' => 'ICA', 'code' => 'ICA', 'percentage' => 0.50, 'is_active' => true]);

        $updateData = [
            'branch_id' => $this->branch->id,
            'tax_id' => $newTax->id,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/admin/branch-taxes/{$branchTax->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'tax_id' => $newTax->id,
                ],
            ]);

        $this->assertDatabaseHas('branch_taxes', [
            'id' => $branchTax->id,
            'tax_id' => $newTax->id,
        ]);
    }

    public function test_can_delete_branch_tax(): void
    {
        $branchTax = BranchTax::create([
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/branch-taxes/{$branchTax->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('branch_taxes', ['id' => $branchTax->id]);
    }

    public function test_cannot_create_branch_tax_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-taxes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id', 'tax_id']);
    }

    public function test_cannot_show_nonexistent_branch_tax(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-taxes/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_nonexistent_branch_tax(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/admin/branch-taxes/9999', [
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_nonexistent_branch_tax(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/admin/branch-taxes/9999');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_to_list_branch_taxes(): void
    {
        $response = $this->getJson('/api/admin/branch-taxes');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_branch_tax(): void
    {
        $response = $this->postJson('/api/admin/branch-taxes', [
            'branch_id' => $this->branch->id,
            'tax_id' => $this->tax->id,
        ]);

        $response->assertStatus(401);
    }
}
