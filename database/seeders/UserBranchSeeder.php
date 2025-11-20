<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Models\UserBranch;
use Illuminate\Database\Seeder;

class UserBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $branches = Branch::all();

        if ($users->isEmpty() || $branches->isEmpty()) {
            return;
        }

        // Assign each user to all branches, with the first branch as primary
        foreach ($users as $index => $user) {
            foreach ($branches as $branchIndex => $branch) {
                UserBranch::firstOrCreate([
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                ], [
                    'is_primary' => $branchIndex === 0, // First branch is primary
                ]);
            }
        }
    }
}
