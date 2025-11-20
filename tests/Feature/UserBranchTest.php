<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\UserBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBranchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $testUser;

    private Branch $branch;

    private Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->testUser = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $this->branch2 = Branch::factory()->create();
    }

    public function test_can_list_user_branches(): void
    {
        UserBranch::create([
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/admin/user-branches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'user_id', 'branch_id', 'is_primary', 'user', 'branch', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_user_branch(): void
    {
        $userBranchData = [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
            'is_primary' => false,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/user-branches', $userBranchData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'user_id', 'branch_id', 'is_primary', 'user', 'branch'],
            ]);

        $this->assertDatabaseHas('user_branches', [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_cannot_create_duplicate_user_branch(): void
    {
        UserBranch::create([
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
        ]);

        $userBranchData = [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/user-branches', $userBranchData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_creating_primary_branch_unsets_other_primary_branches(): void
    {
        UserBranch::create([
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
            'is_primary' => true,
        ]);

        $userBranchData = [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch2->id,
            'is_primary' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/admin/user-branches', $userBranchData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_branches', [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('user_branches', [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch2->id,
            'is_primary' => true,
        ]);
    }

    public function test_can_show_user_branch(): void
    {
        $userBranch = UserBranch::create([
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/admin/user-branches/{$userBranch->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'user_id', 'branch_id', 'is_primary', 'user', 'branch'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $userBranch->id,
                ],
            ]);
    }

    public function test_can_update_user_branch(): void
    {
        $userBranch = UserBranch::create([
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
            'is_primary' => false,
        ]);

        $updateData = [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch2->id,
            'is_primary' => true,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/admin/user-branches/{$userBranch->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'branch_id' => $this->branch2->id,
                    'is_primary' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_branches', [
            'id' => $userBranch->id,
            'branch_id' => $this->branch2->id,
            'is_primary' => true,
        ]);
    }

    public function test_can_delete_user_branch(): void
    {
        $userBranch = UserBranch::create([
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/admin/user-branches/{$userBranch->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_branches', ['id' => $userBranch->id]);
    }

    public function test_cannot_create_user_branch_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/admin/user-branches', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'branch_id']);
    }

    public function test_cannot_show_nonexistent_user_branch(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/user-branches/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_nonexistent_user_branch(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/admin/user-branches/9999', [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_nonexistent_user_branch(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/admin/user-branches/9999');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_to_list_user_branches(): void
    {
        $response = $this->getJson('/api/admin/user-branches');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_user_branch(): void
    {
        $response = $this->postJson('/api/admin/user-branches', [
            'user_id' => $this->testUser->id,
            'branch_id' => $this->branch->id,
        ]);

        $response->assertStatus(401);
    }
}
