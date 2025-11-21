<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    use ApiResponse;

    /**
     * Listar impuestos
     *
     * Obtiene el listado completo de impuestos configurados en el sistema
     * ordenados alfabéticamente por nombre.
     *
     * @OA\Get(
     *     path="/api/admin/taxes",
     *     tags={"Impuestos"},
     *     summary="Listar impuestos",
     *     description="Retorna todos los impuestos registrados en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listado exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Taxes retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1, description="ID único del impuesto"),
     *                     @OA\Property(property="name", type="string", example="IVA", description="Nombre del impuesto"),
     *                     @OA\Property(property="code", type="string", example="IVA", description="Código único del impuesto"),
     *                     @OA\Property(property="percentage", type="number", format="float", example=19.0, description="Porcentaje del impuesto (0-100)"),
     *                     @OA\Property(property="is_active", type="boolean", example=true, description="Estado del impuesto"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
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
        $taxes = Tax::orderBy('name')->get();

        return $this->successResponse($taxes, 'Taxes retrieved successfully');
    }

    /**
     * Crear nuevo impuesto
     *
     * Permite registrar un nuevo impuesto en el sistema con su porcentaje correspondiente.
     * El código debe ser único y el porcentaje debe estar entre 0 y 100.
     *
     * @OA\Post(
     *     path="/api/admin/taxes",
     *     tags={"Impuestos"},
     *     summary="Crear impuesto",
     *     description="Registra un nuevo impuesto en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code", "percentage"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50, example="IVA", description="Nombre descriptivo del impuesto"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="IVA", description="Código único de identificación del impuesto"),
     *             @OA\Property(property="percentage", type="number", format="float", minimum=0, maximum=100, example=19.0, description="Porcentaje del impuesto (0-100)"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Estado del impuesto (opcional, por defecto true)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Impuesto creado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tax created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="IVA"),
     *                 @OA\Property(property="code", type="string", example="IVA"),
     *                 @OA\Property(property="percentage", type="number", format="float", example=19.0),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
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
     *                     property="code",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The code has already been taken.")
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
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:taxes,code',
            'percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $tax = Tax::create($validator->validated());

        return $this->successResponse($tax, 'Tax created successfully', 201);
    }

    /**
     * Obtener impuesto por ID
     *
     * Consulta los detalles completos de un impuesto específico mediante su
     * identificador único. Retorna un error 404 si no existe.
     *
     * @OA\Get(
     *     path="/api/admin/taxes/{id}",
     *     tags={"Impuestos"},
     *     summary="Obtener impuesto",
     *     description="Retorna los detalles de un impuesto específico",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del impuesto",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Impuesto encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tax retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="IVA"),
     *                 @OA\Property(property="code", type="string", example="IVA"),
     *                 @OA\Property(property="percentage", type="number", format="float", example=19.0),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Impuesto no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tax not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The specified tax does not exist.")
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
        $tax = Tax::find($id);

        if (! $tax) {
            return $this->errorResponse('Tax not found', ['id' => ['The specified tax does not exist.']], 404);
        }

        return $this->successResponse($tax, 'Tax retrieved successfully');
    }

    /**
     * Actualizar impuesto existente
     *
     * Modifica los datos de un impuesto específico. Valida que el código
     * sea único (excepto para el registro actual). Retorna error 404 si no existe.
     *
     * @OA\Put(
     *     path="/api/admin/taxes/{id}",
     *     tags={"Impuestos"},
     *     summary="Actualizar impuesto",
     *     description="Modifica los datos de un impuesto existente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del impuesto a actualizar",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code", "percentage"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50, example="IVA", description="Nombre descriptivo del impuesto"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="IVA", description="Código único de identificación del impuesto"),
     *             @OA\Property(property="percentage", type="number", format="float", minimum=0, maximum=100, example=19.0, description="Porcentaje del impuesto (0-100)"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Estado del impuesto")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Impuesto actualizado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tax updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="IVA"),
     *                 @OA\Property(property="code", type="string", example="IVA"),
     *                 @OA\Property(property="percentage", type="number", format="float", example=19.0),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T14:20:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Impuesto no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tax not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The specified tax does not exist.")
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
     *                     property="code",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The code has already been taken.")
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
        $tax = Tax::find($id);

        if (! $tax) {
            return $this->errorResponse('Tax not found', ['id' => ['The specified tax does not exist.']], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:taxes,code,'.$id,
            'percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $tax->update($validator->validated());

        return $this->successResponse($tax, 'Tax updated successfully');
    }

    /**
     * Eliminar impuesto
     *
     * Elimina permanentemente un impuesto del sistema. Esta operación
     * no puede deshacerse. Retorna error 404 si el registro no existe.
     *
     * @OA\Delete(
     *     path="/api/admin/taxes/{id}",
     *     tags={"Impuestos"},
     *     summary="Eliminar impuesto",
     *     description="Elimina permanentemente un impuesto del sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del impuesto a eliminar",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Impuesto eliminado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tax deleted successfully"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Impuesto no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tax not found"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The specified tax does not exist.")
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
        $tax = Tax::find($id);

        if (! $tax) {
            return $this->errorResponse('Tax not found', ['id' => ['The specified tax does not exist.']], 404);
        }

        $tax->delete();

        return $this->successResponse(null, 'Tax deleted successfully');
    }
}
