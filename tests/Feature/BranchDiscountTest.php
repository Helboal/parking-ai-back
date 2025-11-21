<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchDiscount;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Branch $branch;

    private VehicleType $vehicleType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $this->vehicleType = VehicleType::create(['name' => 'Carro', 'code' => 'CAR']);
    }

    public function test_can_list_branch_discounts(): void
    {
        BranchDiscount::create([
            'minutes' => 120,
            'discount_percentage' => 10,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-discounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'minutes', 'discount_percentage', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_branch_discount(): void
    {
        $discountData = [
            'minutes' => 120,
            'discount_percentage' => 10,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-discounts', $discountData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'minutes', 'discount_percentage', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ]);

        $this->assertDatabaseHas('branch_discounts', [
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'minutes' => 120,
            'discount_percentage' => 10,
        ]);
    }

    public function test_can_show_branch_discount(): void
    {
        $discount = BranchDiscount::create([
            'minutes' => 120,
            'discount_percentage' => 10,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/admin/branch-discounts/{$discount->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'minutes', 'discount_percentage', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $discount->id,
                    'minutes' => 120,
                    'discount_percentage' => 10,
                ],
            ]);
    }

    public function test_can_update_branch_discount(): void
    {
        $discount = BranchDiscount::create([
            'minutes' => 120,
            'discount_percentage' => 10,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $updateData = [
            'minutes' => 240,
            'discount_percentage' => 15,
            'is_active' => false,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/admin/branch-discounts/{$discount->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'minutes' => 240,
                    'discount_percentage' => 15,
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('branch_discounts', [
            'id' => $discount->id,
            'minutes' => 240,
            'discount_percentage' => 15,
        ]);
    }

    public function test_can_delete_branch_discount(): void
    {
        $discount = BranchDiscount::create([
            'minutes' => 120,
            'discount_percentage' => 10,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/branch-discounts/{$discount->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('branch_discounts', ['id' => $discount->id]);
    }

    public function test_cannot_create_discount_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-discounts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['minutes', 'discount_percentage', 'branch_id', 'vehicle_type_id']);
    }

    public function test_cannot_create_discount_with_invalid_percentage(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-discounts', [
            'minutes' => 120,
            'discount_percentage' => 150,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_percentage']);
    }

    public function test_cannot_show_nonexistent_discount(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-discounts/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_nonexistent_discount(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/admin/branch-discounts/9999', [
            'minutes' => 120,
            'discount_percentage' => 10,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_nonexistent_discount(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/admin/branch-discounts/9999');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_to_list_discounts(): void
    {
        $response = $this->getJson('/api/admin/branch-discounts');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_discount(): void
    {
        $response = $this->postJson('/api/admin/branch-discounts', [
            'minutes' => 120,
            'discount_percentage' => 10,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(401);
    }
}
