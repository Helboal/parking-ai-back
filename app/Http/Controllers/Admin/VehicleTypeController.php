<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleTypeController extends Controller
{
    use ApiResponse;

    /**
     * Listar tipos de vehículo
     *
     * Obtiene el listado completo de tipos de vehículo disponibles en el sistema
     * (Carro, Moto, Bicicleta, etc.) ordenados alfabéticamente.
     *
     * @OA\Get(
     *     path="/api/admin/vehicle-types",
     *     tags={"Tipos de Vehículo"},
     *     summary="Listar tipos de vehículo",
     *     description="Retorna todos los tipos de vehículo registrados en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listado exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Listado de tipos de vehículo"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Automóvil"),
     *                     @OA\Property(property="code", type="string", example="CAR"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index()
    {
        $vehicleTypes = VehicleType::orderBy('name')->get();

        return $this->successResponse(
            $vehicleTypes,
            'Listado de tipos de vehículo',
            200
        );
    }

    /**
     * Crear nuevo tipo de vehículo
     *
     * @OA\Post(
     *     path="/api/admin/vehicle-types",
     *     tags={"Tipos de Vehículo"},
     *     summary="Crear tipo de vehículo",
     *     description="Registra un nuevo tipo de vehículo en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *
     *             @OA\Property(property="name", type="string", maxLength=60, example="Motocicleta"),
     *             @OA\Property(property="code", type="string", maxLength=10, example="MOTO")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Creado exitosamente"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function store(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:60',
            'code' => 'required|string|max:10|unique:vehicle_types,code',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear tipo de vehículo
        $vehicleType = VehicleType::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return $this->successResponse(
            $vehicleType,
            'Tipo de vehículo creado exitosamente',
            201
        );
    }

    /**
     * Obtener tipo de vehículo por ID
     *
     * @OA\Get(
     *     path="/api/admin/vehicle-types/{id}",
     *     tags={"Tipos de Vehículo"},
     *     summary="Obtener tipo de vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Encontrado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function show(string $id)
    {
        $vehicleType = VehicleType::find($id);

        if (! $vehicleType) {
            return $this->errorResponse(
                'Tipo de vehículo no encontrado',
                ['id' => ['El tipo de vehículo especificado no existe.']],
                404
            );
        }

        return $this->successResponse(
            $vehicleType,
            'Tipo de vehículo encontrado',
            200
        );
    }

    /**
     * Actualizar tipo de vehículo
     *
     * @OA\Put(
     *     path="/api/admin/vehicle-types/{id}",
     *     tags={"Tipos de Vehículo"},
     *     summary="Actualizar tipo de vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *
     *             @OA\Property(property="name", type="string", maxLength=60),
     *             @OA\Property(property="code", type="string", maxLength=10)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Actualizado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function update(Request $request, string $id)
    {
        $vehicleType = VehicleType::find($id);

        if (! $vehicleType) {
            return $this->errorResponse(
                'Tipo de vehículo no encontrado',
                ['id' => ['El tipo de vehículo especificado no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:60',
            'code' => 'required|string|max:10|unique:vehicle_types,code,'.$id,
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar tipo de vehículo
        $vehicleType->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return $this->successResponse(
            $vehicleType,
            'Tipo de vehículo actualizado exitosamente',
            200
        );
    }

    /**
     * Eliminar tipo de vehículo
     *
     * @OA\Delete(
     *     path="/api/admin/vehicle-types/{id}",
     *     tags={"Tipos de Vehículo"},
     *     summary="Eliminar tipo de vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Eliminado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function destroy(string $id)
    {
        $vehicleType = VehicleType::find($id);

        if (! $vehicleType) {
            return $this->errorResponse(
                'Tipo de vehículo no encontrado',
                ['id' => ['El tipo de vehículo especificado no existe.']],
                404
            );
        }

        $vehicleType->delete();

        return $this->successResponse(
            null,
            'Tipo de vehículo eliminado exitosamente',
            200
        );
    }
}
