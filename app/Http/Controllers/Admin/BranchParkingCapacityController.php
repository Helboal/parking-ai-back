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
     * Listar capacidades de parqueo por sucursal
     *
     * Obtiene el listado completo de capacidades configuradas para cada sucursal
     * según el tipo de vehículo, mostrando espacios totales y ocupados.
     *
     * @OA\Get(
     *     path="/api/admin/branch-parking-capacities",
     *     tags={"Capacidad de Parqueo por Sucursal"},
     *     summary="Listar capacidades de parqueo",
     *     description="Retorna todas las configuraciones de capacidad por sucursal y tipo de vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listado exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Branch parking capacities retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="total_spaces", type="integer", example=50),
     *                     @OA\Property(property="occupied_spaces", type="integer", example=20),
     *                     @OA\Property(property="branch_id", type="integer", example=1),
     *                     @OA\Property(property="vehicle_type_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
     * Crear nueva capacidad de parqueo
     *
     * Permite registrar la configuración de capacidad para una sucursal específica
     * según el tipo de vehículo. Los espacios ocupados no pueden exceder los totales.
     *
     * @OA\Post(
     *     path="/api/admin/branch-parking-capacities",
     *     tags={"Capacidad de Parqueo por Sucursal"},
     *     summary="Crear capacidad de parqueo",
     *     description="Registra una nueva configuración de capacidad para una sucursal",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"total_spaces", "occupied_spaces", "branch_id", "vehicle_type_id"},
     *
     *             @OA\Property(property="total_spaces", type="integer", minimum=1, example=50, description="Espacios totales disponibles"),
     *             @OA\Property(property="occupied_spaces", type="integer", minimum=0, example=20, description="Espacios actualmente ocupados"),
     *             @OA\Property(property="branch_id", type="integer", example=1, description="ID de la sucursal"),
     *             @OA\Property(property="vehicle_type_id", type="integer", example=1, description="ID del tipo de vehículo")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Capacidad creada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Branch parking capacity created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="total_spaces", type="integer", example=50),
     *                 @OA\Property(property="occupied_spaces", type="integer", example=20),
     *                 @OA\Property(property="branch_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle_type_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="occupied_spaces",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Occupied spaces cannot exceed total spaces.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
     * Obtener capacidad de parqueo por ID
     *
     * Consulta los detalles completos de una configuración de capacidad específica
     * mediante su identificador único. Retorna un error 404 si no existe.
     *
     * @OA\Get(
     *     path="/api/admin/branch-parking-capacities/{id}",
     *     tags={"Capacidad de Parqueo por Sucursal"},
     *     summary="Obtener capacidad de parqueo",
     *     description="Retorna los detalles de una configuración de capacidad específica",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la capacidad de parqueo",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Capacidad encontrada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Branch parking capacity retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="total_spaces", type="integer", example=50),
     *                 @OA\Property(property="occupied_spaces", type="integer", example=20),
     *                 @OA\Property(property="branch_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle_type_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Capacidad no encontrada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Branch parking capacity not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The specified branch parking capacity does not exist.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
     * Actualizar capacidad de parqueo existente
     *
     * Modifica los datos de una configuración de capacidad específica. Valida que
     * los espacios ocupados no excedan los totales. Retorna error 404 si no existe.
     *
     * @OA\Put(
     *     path="/api/admin/branch-parking-capacities/{id}",
     *     tags={"Capacidad de Parqueo por Sucursal"},
     *     summary="Actualizar capacidad de parqueo",
     *     description="Modifica los datos de una configuración de capacidad existente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la capacidad a actualizar",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"total_spaces", "occupied_spaces", "branch_id", "vehicle_type_id"},
     *
     *             @OA\Property(property="total_spaces", type="integer", minimum=1, example=60, description="Espacios totales disponibles"),
     *             @OA\Property(property="occupied_spaces", type="integer", minimum=0, example=25, description="Espacios actualmente ocupados"),
     *             @OA\Property(property="branch_id", type="integer", example=1, description="ID de la sucursal"),
     *             @OA\Property(property="vehicle_type_id", type="integer", example=1, description="ID del tipo de vehículo")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Capacidad actualizada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Branch parking capacity updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="total_spaces", type="integer", example=60),
     *                 @OA\Property(property="occupied_spaces", type="integer", example=25),
     *                 @OA\Property(property="branch_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle_type_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Capacidad no encontrada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Branch parking capacity not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The specified branch parking capacity does not exist.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="occupied_spaces",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Occupied spaces cannot exceed total spaces.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
     * Eliminar capacidad de parqueo
     *
     * Elimina permanentemente una configuración de capacidad del sistema.
     * Esta operación no puede deshacerse. Retorna error 404 si no existe.
     *
     * @OA\Delete(
     *     path="/api/admin/branch-parking-capacities/{id}",
     *     tags={"Capacidad de Parqueo por Sucursal"},
     *     summary="Eliminar capacidad de parqueo",
     *     description="Elimina permanentemente una configuración de capacidad del sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la capacidad a eliminar",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Capacidad eliminada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Branch parking capacity deleted successfully"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Capacidad no encontrada",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Branch parking capacity not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The specified branch parking capacity does not exist.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
