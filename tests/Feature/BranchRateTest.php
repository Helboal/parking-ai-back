<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchRate;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchRateTest extends TestCase
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

    public function test_can_list_branch_rates(): void
    {
        BranchRate::create([
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'rate_per_minute', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_branch_rate(): void
    {
        $rateData = [
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-rates', $rateData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'rate_per_minute', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ]);

        $this->assertDatabaseHas('branch_rates', [
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'rate_per_minute' => 50.00,
        ]);
    }

    public function test_cannot_create_duplicate_branch_vehicle_rate(): void
    {
        BranchRate::create([
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $rateData = [
            'rate_per_minute' => 40.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-rates', $rateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    public function test_can_show_branch_rate(): void
    {
        $rate = BranchRate::create([
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/admin/branch-rates/{$rate->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'rate_per_minute', 'is_active', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $rate->id,
                    'rate_per_minute' => '50.00',
                ],
            ]);
    }

    public function test_can_update_branch_rate(): void
    {
        $rate = BranchRate::create([
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $updateData = [
            'rate_per_minute' => 60.00,
            'is_active' => false,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/admin/branch-rates/{$rate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'rate_per_minute' => '60.00',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('branch_rates', [
            'id' => $rate->id,
            'rate_per_minute' => 60.00,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_branch_rate(): void
    {
        $rate = BranchRate::create([
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/branch-rates/{$rate->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('branch_rates', ['id' => $rate->id]);
    }

    public function test_cannot_create_rate_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-rates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate_per_minute', 'branch_id', 'vehicle_type_id']);
    }

    public function test_cannot_show_nonexistent_rate(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-rates/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_nonexistent_rate(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/admin/branch-rates/9999', [
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_nonexistent_rate(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/admin/branch-rates/9999');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_to_list_rates(): void
    {
        $response = $this->getJson('/api/admin/branch-rates');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_rate(): void
    {
        $response = $this->postJson('/api/admin/branch-rates', [
            'rate_per_minute' => 50.00,
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(401);
    }
}
