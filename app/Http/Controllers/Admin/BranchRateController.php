<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchRate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchRateController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $rates = BranchRate::with(['branch', 'vehicleType'])
            ->orderBy('branch_id')
            ->orderBy('vehicle_type_id')
            ->get();

        return $this->successResponse($rates, 'Branch rates retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rate_per_minute' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        // Check for duplicate branch_id + vehicle_type_id
        $exists = BranchRate::where('branch_id', $request->branch_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This branch already has a rate configured for this vehicle type.']],
                422
            );
        }

        $rate = BranchRate::create($validator->validated());
        $rate->load(['branch', 'vehicleType']);

        return $this->successResponse($rate, 'Branch rate created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $rate = BranchRate::with(['branch', 'vehicleType'])->find($id);

        if (! $rate) {
            return $this->errorResponse(
                'Branch rate not found',
                ['id' => ['The specified branch rate does not exist.']],
                404
            );
        }

        return $this->successResponse($rate, 'Branch rate retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $rate = BranchRate::find($id);

        if (! $rate) {
            return $this->errorResponse(
                'Branch rate not found',
                ['id' => ['The specified branch rate does not exist.']],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'rate_per_minute' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        // Check for duplicate branch_id + vehicle_type_id (excluding current record)
        $exists = BranchRate::where('branch_id', $request->branch_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This branch already has a rate configured for this vehicle type.']],
                422
            );
        }

        $rate->update($validator->validated());
        $rate->load(['branch', 'vehicleType']);

        return $this->successResponse($rate, 'Branch rate updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $rate = BranchRate::find($id);

        if (! $rate) {
            return $this->errorResponse(
                'Branch rate not found',
                ['id' => ['The specified branch rate does not exist.']],
                404
            );
        }

        $rate->delete();

        return $this->successResponse(null, 'Branch rate deleted successfully');
    }
}
