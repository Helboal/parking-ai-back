<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchFlatRate;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchFlatRateTest extends TestCase
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

    public function test_can_list_branch_flat_rates(): void
    {
        BranchFlatRate::create([
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-flat-rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'minuts_threshold', 'flat_rate', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_branch_flat_rate(): void
    {
        $flatRateData = [
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-flat-rates', $flatRateData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'minuts_threshold', 'flat_rate', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ]);

        $this->assertDatabaseHas('branch_flat_rates', [
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'minuts_threshold' => 720,
        ]);
    }

    public function test_cannot_create_duplicate_branch_vehicle_flat_rate(): void
    {
        BranchFlatRate::create([
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $flatRateData = [
            'minuts_threshold' => 600,
            'flat_rate' => 25000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-flat-rates', $flatRateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    public function test_can_show_branch_flat_rate(): void
    {
        $flatRate = BranchFlatRate::create([
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/admin/branch-flat-rates/{$flatRate->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'minuts_threshold', 'flat_rate', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $flatRate->id,
                    'flat_rate' => '30000.00',
                ],
            ]);
    }

    public function test_can_update_branch_flat_rate(): void
    {
        $flatRate = BranchFlatRate::create([
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $updateData = [
            'minuts_threshold' => 600,
            'flat_rate' => 25000.00,
            'is_active' => false,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/admin/branch-flat-rates/{$flatRate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'minuts_threshold' => 600,
                    'flat_rate' => '25000.00',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('branch_flat_rates', [
            'id' => $flatRate->id,
            'flat_rate' => 25000.00,
        ]);
    }

    public function test_can_delete_branch_flat_rate(): void
    {
        $flatRate = BranchFlatRate::create([
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/branch-flat-rates/{$flatRate->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('branch_flat_rates', ['id' => $flatRate->id]);
    }

    public function test_cannot_create_flat_rate_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-flat-rates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['minuts_threshold', 'flat_rate', 'branch_id', 'vehicle_type_id']);
    }

    public function test_cannot_show_nonexistent_flat_rate(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-flat-rates/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_nonexistent_flat_rate(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/admin/branch-flat-rates/9999', [
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_nonexistent_flat_rate(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/admin/branch-flat-rates/9999');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_to_list_flat_rates(): void
    {
        $response = $this->getJson('/api/admin/branch-flat-rates');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_flat_rate(): void
    {
        $response = $this->postJson('/api/admin/branch-flat-rates', [
            'minuts_threshold' => 720,
            'flat_rate' => 30000.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(401);
    }
}
