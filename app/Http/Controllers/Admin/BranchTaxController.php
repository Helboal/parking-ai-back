<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchTax;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchTaxController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $branchTaxes = BranchTax::with(['branch', 'tax'])->orderBy('branch_id')->orderBy('tax_id')->get();

        return $this->successResponse($branchTaxes, 'Branch taxes retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'tax_id' => 'required|exists:taxes,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $exists = BranchTax::where('branch_id', $request->branch_id)
            ->where('tax_id', $request->tax_id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This tax is already assigned to this branch.']],
                422
            );
        }

        $branchTax = BranchTax::create($validator->validated());
        $branchTax->load(['branch', 'tax']);

        return $this->successResponse($branchTax, 'Branch tax created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $branchTax = BranchTax::with(['branch', 'tax'])->find($id);

        if (! $branchTax) {
            return $this->errorResponse(
                'Branch tax not found',
                ['id' => ['The specified branch tax does not exist.']],
                404
            );
        }

        return $this->successResponse($branchTax, 'Branch tax retrieved successfully');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $branchTax = BranchTax::find($id);

        if (! $branchTax) {
            return $this->errorResponse(
                'Branch tax not found',
                ['id' => ['The specified branch tax does not exist.']],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'tax_id' => 'required|exists:taxes,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $exists = BranchTax::where('branch_id', $request->branch_id)
            ->where('tax_id', $request->tax_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This tax is already assigned to this branch.']],
                422
            );
        }

        $branchTax->update($validator->validated());
        $branchTax->load(['branch', 'tax']);

        return $this->successResponse($branchTax, 'Branch tax updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $branchTax = BranchTax::find($id);

        if (! $branchTax) {
            return $this->errorResponse(
                'Branch tax not found',
                ['id' => ['The specified branch tax does not exist.']],
                404
            );
        }

        $branchTax->delete();

        return $this->successResponse(null, 'Branch tax deleted successfully');
    }
}
