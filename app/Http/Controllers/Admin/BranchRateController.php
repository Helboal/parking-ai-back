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
     * Listar tarifas por sucursal
     *
     * Obtiene el listado completo de tarifas por minuto configuradas para cada
     * sucursal según el tipo de vehículo.
     *
     * @OA\Get(
     *     path="/api/admin/branch-rates",
     *     tags={"Tarifas por Sucursal"},
     *     summary="Listar tarifas por sucursal",
     *     description="Retorna todas las tarifas configuradas por sucursal y tipo de vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listado exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Branch rates retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="rate_per_minute", type="number", format="float", example=50.00),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="branch_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle_type_id", type="integer", example=1)
     *             ))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     * Crear nueva tarifa por sucursal
     *
     * Permite registrar una tarifa por minuto para una sucursal específica
     * según el tipo de vehículo.
     *
     * @OA\Post(
     *     path="/api/admin/branch-rates",
     *     tags={"Tarifas por Sucursal"},
     *     summary="Crear tarifa por sucursal",
     *     description="Registra una nueva tarifa para una sucursal",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"rate_per_minute", "branch_id", "vehicle_type_id"},
     *
     *             @OA\Property(property="rate_per_minute", type="number", format="float", minimum=0, example=50.00),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="branch_id", type="integer", example=1),
     *             @OA\Property(property="vehicle_type_id", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Tarifa creada exitosamente"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     * Obtener tarifa por ID
     *
     * @OA\Get(
     *     path="/api/admin/branch-rates/{id}",
     *     tags={"Tarifas por Sucursal"},
     *     summary="Obtener tarifa por sucursal",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Tarifa encontrada"),
     *     @OA\Response(response=404, description="Tarifa no encontrada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
