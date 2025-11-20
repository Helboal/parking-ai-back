<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchParkingCapacity;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchParkingCapacityTest extends TestCase
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

    public function test_can_list_branch_parking_capacities(): void
    {
        BranchParkingCapacity::create([
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-parking-capacity');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'total_spaces', 'occupied_spaces', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_branch_parking_capacity(): void
    {
        $capacityData = [
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-parking-capacity', $capacityData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'total_spaces', 'occupied_spaces', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ]);

        $this->assertDatabaseHas('branch_parking_capacity', [
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
            'total_spaces' => 50,
        ]);
    }

    public function test_cannot_create_capacity_when_occupied_exceeds_total(): void
    {
        $capacityData = [
            'total_spaces' => 50,
            'occupied_spaces' => 60,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-parking-capacity', $capacityData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['occupied_spaces']);
    }

    public function test_cannot_create_duplicate_branch_vehicle_capacity(): void
    {
        BranchParkingCapacity::create([
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $capacityData = [
            'total_spaces' => 30,
            'occupied_spaces' => 5,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-parking-capacity', $capacityData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    public function test_can_show_branch_parking_capacity(): void
    {
        $capacity = BranchParkingCapacity::create([
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/admin/branch-parking-capacity/{$capacity->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'total_spaces', 'occupied_spaces', 'branch_id', 'vehicle_type_id', 'branch', 'vehicle_type'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $capacity->id,
                    'total_spaces' => 50,
                ],
            ]);
    }

    public function test_can_update_branch_parking_capacity(): void
    {
        $capacity = BranchParkingCapacity::create([
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $updateData = [
            'total_spaces' => 60,
            'occupied_spaces' => 15,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/admin/branch-parking-capacity/{$capacity->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_spaces' => 60,
                    'occupied_spaces' => 15,
                ],
            ]);

        $this->assertDatabaseHas('branch_parking_capacity', [
            'id' => $capacity->id,
            'total_spaces' => 60,
            'occupied_spaces' => 15,
        ]);
    }

    public function test_can_delete_branch_parking_capacity(): void
    {
        $capacity = BranchParkingCapacity::create([
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/branch-parking-capacity/{$capacity->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('branch_parking_capacity', ['id' => $capacity->id]);
    }

    public function test_cannot_create_capacity_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/branch-parking-capacity', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_spaces', 'occupied_spaces', 'branch_id', 'vehicle_type_id']);
    }

    public function test_cannot_show_nonexistent_capacity(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/branch-parking-capacity/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_nonexistent_capacity(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/admin/branch-parking-capacity/9999', [
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_nonexistent_capacity(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/admin/branch-parking-capacity/9999');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_to_list_capacities(): void
    {
        $response = $this->getJson('/api/admin/branch-parking-capacity');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_capacity(): void
    {
        $response = $this->postJson('/api/admin/branch-parking-capacity', [
            'total_spaces' => 50,
            'occupied_spaces' => 10,
            'branch_id' => $this->branch->id,
            'vehicle_type_id' => $this->vehicleType->id,
        ]);

        $response->assertStatus(401);
    }
}
