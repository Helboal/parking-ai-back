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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
