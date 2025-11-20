<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserBranch;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserBranchController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $userBranches = UserBranch::with(['user', 'branch'])
            ->orderBy('user_id')
            ->orderBy('is_primary', 'desc')
            ->orderBy('branch_id')
            ->get();

        return $this->successResponse($userBranches, 'User branches retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $exists = UserBranch::where('user_id', $request->user_id)
            ->where('branch_id', $request->branch_id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['user_id' => ['This user is already assigned to this branch.']],
                422
            );
        }

        // If this is set as primary, unset other primary branches for this user
        if ($request->input('is_primary', false)) {
            UserBranch::where('user_id', $request->user_id)
                ->update(['is_primary' => false]);
        }

        $userBranch = UserBranch::create($validator->validated());
        $userBranch->load(['user', 'branch']);

        return $this->successResponse($userBranch, 'User branch created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $userBranch = UserBranch::with(['user', 'branch'])->find($id);

        if (! $userBranch) {
            return $this->errorResponse(
                'User branch not found',
                ['id' => ['The specified user branch does not exist.']],
                404
            );
        }

        return $this->successResponse($userBranch, 'User branch retrieved successfully');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $userBranch = UserBranch::find($id);

        if (! $userBranch) {
            return $this->errorResponse(
                'User branch not found',
                ['id' => ['The specified user branch does not exist.']],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $exists = UserBranch::where('user_id', $request->user_id)
            ->where('branch_id', $request->branch_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['user_id' => ['This user is already assigned to this branch.']],
                422
            );
        }

        // If this is set as primary, unset other primary branches for this user
        if ($request->input('is_primary', false)) {
            UserBranch::where('user_id', $request->user_id)
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);
        }

        $userBranch->update($validator->validated());
        $userBranch->load(['user', 'branch']);

        return $this->successResponse($userBranch, 'User branch updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $userBranch = UserBranch::find($id);

        if (! $userBranch) {
            return $this->errorResponse(
                'User branch not found',
                ['id' => ['The specified user branch does not exist.']],
                404
            );
        }

        $userBranch->delete();

        return $this->successResponse(null, 'User branch deleted successfully');
    }
}
