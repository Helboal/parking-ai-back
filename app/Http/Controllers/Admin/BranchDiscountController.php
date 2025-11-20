<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchDiscount;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchDiscountController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $discounts = BranchDiscount::with(['branch', 'vehicleType'])
            ->orderBy('branch_id')
            ->orderBy('vehicle_type_id')
            ->orderBy('minuts')
            ->get();

        return $this->successResponse($discounts, 'Branch discounts retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'minuts' => 'required|integer|min:1',
            'discount_percentage' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $discount = BranchDiscount::create($validator->validated());
        $discount->load(['branch', 'vehicleType']);

        return $this->successResponse($discount, 'Branch discount created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $discount = BranchDiscount::with(['branch', 'vehicleType'])->find($id);

        if (! $discount) {
            return $this->errorResponse(
                'Branch discount not found',
                ['id' => ['The specified branch discount does not exist.']],
                404
            );
        }

        return $this->successResponse($discount, 'Branch discount retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $discount = BranchDiscount::find($id);

        if (! $discount) {
            return $this->errorResponse(
                'Branch discount not found',
                ['id' => ['The specified branch discount does not exist.']],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'minuts' => 'required|integer|min:1',
            'discount_percentage' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $discount->update($validator->validated());
        $discount->load(['branch', 'vehicleType']);

        return $this->successResponse($discount, 'Branch discount updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $discount = BranchDiscount::find($id);

        if (! $discount) {
            return $this->errorResponse(
                'Branch discount not found',
                ['id' => ['The specified branch discount does not exist.']],
                404
            );
        }

        $discount->delete();

        return $this->successResponse(null, 'Branch discount deleted successfully');
    }
}
