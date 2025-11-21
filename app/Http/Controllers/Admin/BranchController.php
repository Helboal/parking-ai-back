<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    use ApiResponse;

    /**
     * Listar sucursales
     *
     * Obtiene todas las sucursales del sistema
     *
     * @OA\Get(
     *     path="/api/admin/branches",
     *     tags={"Sucursales"},
     *     summary="Listar sucursales",
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
        $branches = Branch::all();

        return $this->successResponse(
            $branches,
            'Listado de sedes',
            200
        );
    }

    /**
     * Crear sucursal
     *
     * @OA\Post(
     *     path="/api/admin/branches",
     *     tags={"Sucursales"},
     *     summary="Crear sucursal",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code", "address"},
     *
     *             @OA\Property(property="name", type="string", maxLength=100, example="Sede Norte"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="NORTE"),
     *             @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="3001234567"),
     *             @OA\Property(property="email", type="string", maxLength=100, example="norte@parqueadero.com"),
     *             @OA\Property(property="opening_time", type="string", format="time", example="06:00"),
     *             @OA\Property(property="closing_time", type="string", format="time", example="22:00"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Creada exitosamente"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:branches,name',
            'code' => 'required|string|max:20|unique:branches,code',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear sede
        $branch = Branch::create([
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'is_active' => $request->is_active ?? true,
        ]);

        return $this->successResponse(
            $branch,
            'Sede creada exitosamente',
            201
        );
    }

    /**
     * Obtener sucursal por ID
     *
     * @OA\Get(
     *     path="/api/admin/branches/{id}",
     *     tags={"Sucursales"},
     *     summary="Obtener sucursal",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Encontrada exitosamente"),
     *     @OA\Response(response=404, description="No encontrada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            return $this->errorResponse(
                'Sede no encontrada',
                ['branch' => ['La sede no existe.']],
                404
            );
        }

        return $this->successResponse(
            $branch,
            'Sede encontrada',
            200
        );
    }

    /**
     * Actualizar sucursal
     *
     * @OA\Put(
     *     path="/api/admin/branches/{id}",
     *     tags={"Sucursales"},
     *     summary="Actualizar sucursal",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code", "address"},
     *
     *             @OA\Property(property="name", type="string", maxLength=100, example="Sede Norte"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="NORTE"),
     *             @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="3001234567"),
     *             @OA\Property(property="email", type="string", maxLength=100, example="norte@parqueadero.com"),
     *             @OA\Property(property="opening_time", type="string", format="time", example="06:00"),
     *             @OA\Property(property="closing_time", type="string", format="time", example="22:00"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Actualizada exitosamente"),
     *     @OA\Response(response=404, description="No encontrada"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            return $this->errorResponse(
                'Sede no encontrada',
                ['branch' => ['La sede no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('branches')->ignore($branch->id),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('branches')->ignore($branch->id),
            ],
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar sede
        $branch->update([
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'is_active' => $request->is_active ?? $branch->is_active,
        ]);

        return $this->successResponse(
            $branch,
            'Sede actualizada exitosamente',
            200
        );
    }

    /**
     * Eliminar sucursal
     *
     * @OA\Delete(
     *     path="/api/admin/branches/{id}",
     *     tags={"Sucursales"},
     *     summary="Eliminar sucursal",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Eliminada exitosamente"),
     *     @OA\Response(response=404, description="No encontrada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            return $this->errorResponse(
                'Sede no encontrada',
                ['branch' => ['La sede no existe.']],
                404
            );
        }

        $branch->delete();

        return $this->successResponse(
            null,
            'Sede eliminada exitosamente',
            200
        );
    }
}
