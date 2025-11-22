<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    use ApiResponse;

    /**
     * Listar vehículos
     *
     * @OA\Get(
     *     path="/api/admin/vehicles",
     *     tags={"Vehículos"},
     *     summary="Listar vehículos",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Listado exitoso"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $vehicles = Vehicle::with(['customer', 'vehicleType'])->get();

        return $this->successResponse(
            $vehicles,
            'Listado de vehículos',
            200
        );
    }

    /**
     * Crear vehículo
     *
     * @OA\Post(
     *     path="/api/admin/vehicles",
     *     tags={"Vehículos"},
     *     summary="Crear vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"license_plate", "customer_id", "vehicle_type_id"},
     *
     *             @OA\Property(property="license_plate", type="string", maxLength=20, example="ABC123"),
     *             @OA\Property(property="brand", type="string", maxLength=50, example="Chevrolet"),
     *             @OA\Property(property="model", type="string", maxLength=50, example="Spark GT"),
     *             @OA\Property(property="color", type="string", maxLength=30, example="Rojo"),
     *             @OA\Property(property="year", type="integer", example=2022),
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="vehicle_type_id", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Creado exitosamente"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $currentYear = date('Y');

        // Convertir placa a mayúsculas antes de validar
        $request->merge(['license_plate' => strtoupper($request->license_plate)]);

        // Validación
        $validator = Validator::make($request->all(), [
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'year' => 'nullable|integer|min:1900|max:'.($currentYear + 1),
            'customer_id' => 'required|exists:customers,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ], [
            'license_plate.required' => 'La placa es requerida.',
            'license_plate.unique' => 'Ya existe un vehículo con esta placa.',
            'brand.max' => 'La marca no debe exceder 50 caracteres.',
            'model.max' => 'El modelo no debe exceder 50 caracteres.',
            'color.max' => 'El color no debe exceder 30 caracteres.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.min' => 'El año debe ser mayor o igual a 1900.',
            'year.max' => 'El año no puede ser mayor a '.($currentYear + 1).'.',
            'customer_id.required' => 'El cliente es requerido.',
            'customer_id.exists' => 'El cliente no existe.',
            'vehicle_type_id.required' => 'El tipo de vehículo es requerido.',
            'vehicle_type_id.exists' => 'El tipo de vehículo no existe.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear vehículo (la placa ya fue convertida a mayúsculas antes de validar)
        $vehicle = Vehicle::create([
            'license_plate' => $request->license_plate,
            'brand' => $request->brand,
            'model' => $request->model,
            'color' => $request->color,
            'year' => $request->year,
            'customer_id' => $request->customer_id,
            'vehicle_type_id' => $request->vehicle_type_id,
        ]);

        // Cargar relaciones
        $vehicle->load(['customer', 'vehicleType']);

        return $this->successResponse(
            $vehicle,
            'Vehículo creado exitosamente',
            201
        );
    }

    /**
     * Obtener vehículo por ID
     *
     * @OA\Get(
     *     path="/api/admin/vehicles/{id}",
     *     tags={"Vehículos"},
     *     summary="Obtener vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Encontrado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $vehicle = Vehicle::with(['customer', 'vehicleType'])->find($id);

        if (! $vehicle) {
            return $this->errorResponse(
                'Vehículo no encontrado',
                ['vehicle' => ['El vehículo no existe.']],
                404
            );
        }

        return $this->successResponse(
            $vehicle,
            'Vehículo encontrado',
            200
        );
    }

    /**
     * Actualizar vehículo
     *
     * @OA\Put(
     *     path="/api/admin/vehicles/{id}",
     *     tags={"Vehículos"},
     *     summary="Actualizar vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"license_plate", "customer_id", "vehicle_type_id"},
     *
     *             @OA\Property(property="license_plate", type="string", maxLength=20),
     *             @OA\Property(property="brand", type="string", maxLength=50),
     *             @OA\Property(property="model", type="string", maxLength=50),
     *             @OA\Property(property="color", type="string", maxLength=30),
     *             @OA\Property(property="year", type="integer"),
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="vehicle_type_id", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Actualizado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (! $vehicle) {
            return $this->errorResponse(
                'Vehículo no encontrado',
                ['vehicle' => ['El vehículo no existe.']],
                404
            );
        }

        $currentYear = date('Y');

        // Validación
        $validator = Validator::make($request->all(), [
            'license_plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles')->ignore($vehicle->id),
            ],
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'year' => 'nullable|integer|min:1900|max:'.($currentYear + 1),
            'customer_id' => 'required|exists:customers,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ], [
            'license_plate.required' => 'La placa es requerida.',
            'license_plate.unique' => 'Ya existe un vehículo con esta placa.',
            'brand.max' => 'La marca no debe exceder 50 caracteres.',
            'model.max' => 'El modelo no debe exceder 50 caracteres.',
            'color.max' => 'El color no debe exceder 30 caracteres.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.min' => 'El año debe ser mayor o igual a 1900.',
            'year.max' => 'El año no puede ser mayor a '.($currentYear + 1).'.',
            'customer_id.required' => 'El cliente es requerido.',
            'customer_id.exists' => 'El cliente no existe.',
            'vehicle_type_id.required' => 'El tipo de vehículo es requerido.',
            'vehicle_type_id.exists' => 'El tipo de vehículo no existe.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Convertir placa a mayúsculas
        $licensePlate = strtoupper($request->license_plate);

        // Actualizar vehículo
        $vehicle->update([
            'license_plate' => $licensePlate,
            'brand' => $request->brand,
            'model' => $request->model,
            'color' => $request->color,
            'year' => $request->year,
            'customer_id' => $request->customer_id,
            'vehicle_type_id' => $request->vehicle_type_id,
        ]);

        // Cargar relaciones
        $vehicle->load(['customer', 'vehicleType']);

        return $this->successResponse(
            $vehicle,
            'Vehículo actualizado exitosamente',
            200
        );
    }

    /**
     * Eliminar vehículo
     *
     * @OA\Delete(
     *     path="/api/admin/vehicles/{id}",
     *     tags={"Vehículos"},
     *     summary="Eliminar vehículo",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Eliminado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::find($id);

        if (! $vehicle) {
            return $this->errorResponse(
                'Vehículo no encontrado',
                ['vehicle' => ['El vehículo no existe.']],
                404
            );
        }

        $vehicle->delete();

        return $this->successResponse(
            null,
            'Vehículo eliminado exitosamente',
            200
        );
    }
}
