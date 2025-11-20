<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchParkingCapacity;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchParkingCapacityController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $capacities = BranchParkingCapacity::with(['branch', 'vehicleType'])
            ->orderBy('branch_id')
            ->orderBy('vehicle_type_id')
            ->get();

        return $this->successResponse($capacities, 'Branch parking capacities retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_spaces' => 'required|integer|min:1',
            'occupied_spaces' => 'required|integer|min:0',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        // Validate occupied_spaces <= total_spaces
        if ($request->occupied_spaces > $request->total_spaces) {
            return $this->errorResponse(
                'Validation failed',
                ['occupied_spaces' => ['Occupied spaces cannot exceed total spaces.']],
                422
            );
        }

        // Check for duplicate branch_id + vehicle_type_id
        $exists = BranchParkingCapacity::where('branch_id', $request->branch_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This branch already has capacity configured for this vehicle type.']],
                422
            );
        }

        $capacity = BranchParkingCapacity::create($validator->validated());
        $capacity->load(['branch', 'vehicleType']);

        return $this->successResponse($capacity, 'Branch parking capacity created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $capacity = BranchParkingCapacity::with(['branch', 'vehicleType'])->find($id);

        if (! $capacity) {
            return $this->errorResponse(
                'Branch parking capacity not found',
                ['id' => ['The specified branch parking capacity does not exist.']],
                404
            );
        }

        return $this->successResponse($capacity, 'Branch parking capacity retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $capacity = BranchParkingCapacity::find($id);

        if (! $capacity) {
            return $this->errorResponse(
                'Branch parking capacity not found',
                ['id' => ['The specified branch parking capacity does not exist.']],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'total_spaces' => 'required|integer|min:1',
            'occupied_spaces' => 'required|integer|min:0',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        // Validate occupied_spaces <= total_spaces
        if ($request->occupied_spaces > $request->total_spaces) {
            return $this->errorResponse(
                'Validation failed',
                ['occupied_spaces' => ['Occupied spaces cannot exceed total spaces.']],
                422
            );
        }

        // Check for duplicate branch_id + vehicle_type_id (excluding current record)
        $exists = BranchParkingCapacity::where('branch_id', $request->branch_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Validation failed',
                ['branch_id' => ['This branch already has capacity configured for this vehicle type.']],
                422
            );
        }

        $capacity->update($validator->validated());
        $capacity->load(['branch', 'vehicleType']);

        return $this->successResponse($capacity, 'Branch parking capacity updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $capacity = BranchParkingCapacity::find($id);

        if (! $capacity) {
            return $this->errorResponse(
                'Branch parking capacity not found',
                ['id' => ['The specified branch parking capacity does not exist.']],
                404
            );
        }

        $capacity->delete();

        return $this->successResponse(null, 'Branch parking capacity deleted successfully');
    }
}
