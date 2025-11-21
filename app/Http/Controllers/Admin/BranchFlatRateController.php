<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchFlatRate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchFlatRateController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $flatRates = BranchFlatRate::with(['branch', 'vehicleType'])
            ->orderBy('branch_id')
            ->orderBy('vehicle_type_id')
            ->get();

        return $this->successResponse($flatRates, 'Branch flat rates retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'minutes_threshold' => 'required|integer|min:1',
            'flat_rate_amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $exists = BranchFlatRate::where('branch_id', $request->branch_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This branch already has a flat rate configured for this vehicle type.']],
                422
            );
        }

        $flatRate = BranchFlatRate::create($validator->validated());
        $flatRate->load(['branch', 'vehicleType']);

        return $this->successResponse($flatRate, 'Branch flat rate created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $flatRate = BranchFlatRate::with(['branch', 'vehicleType'])->find($id);

        if (! $flatRate) {
            return $this->errorResponse(
                'Branch flat rate not found',
                ['id' => ['The specified branch flat rate does not exist.']],
                404
            );
        }

        return $this->successResponse($flatRate, 'Branch flat rate retrieved successfully');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $flatRate = BranchFlatRate::find($id);

        if (! $flatRate) {
            return $this->errorResponse(
                'Branch flat rate not found',
                ['id' => ['The specified branch flat rate does not exist.']],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'minutes_threshold' => 'required|integer|min:1',
            'flat_rate_amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $exists = BranchFlatRate::where('branch_id', $request->branch_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This branch already has a flat rate configured for this vehicle type.']],
                422
            );
        }

        $flatRate->update($validator->validated());
        $flatRate->load(['branch', 'vehicleType']);

        return $this->successResponse($flatRate, 'Branch flat rate updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $flatRate = BranchFlatRate::find($id);

        if (! $flatRate) {
            return $this->errorResponse(
                'Branch flat rate not found',
                ['id' => ['The specified branch flat rate does not exist.']],
                404
            );
        }

        $flatRate->delete();

        return $this->successResponse(null, 'Branch flat rate deleted successfully');
    }
}
