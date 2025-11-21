<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntryType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EntryTypeController extends Controller
{
    use ApiResponse;

    /**
     * Listar tipos de entrada
     *
     * Obtiene el listado completo de tipos de entrada disponibles en el sistema
     * (Regular, Suscripción, etc.) ordenados alfabéticamente.
     *
     * @OA\Get(
     *     path="/api/admin/entry-types",
     *     tags={"Tipos de Entrada"},
     *     summary="Listar tipos de entrada",
     *     description="Retorna todos los tipos de entrada registrados en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listado exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Listado de tipos de entrada"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1, description="ID único del tipo de entrada"),
     *                     @OA\Property(property="name", type="string", example="Entrada Regular", description="Nombre del tipo de entrada"),
     *                     @OA\Property(property="code", type="string", example="REGULAR", description="Código único del tipo de entrada"),
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
    public function index()
    {
        $entryTypes = EntryType::orderBy('name')->get();

        return $this->successResponse(
            $entryTypes,
            'Listado de tipos de entrada',
            200
        );
    }

    /**
     * Crear nuevo tipo de entrada
     *
     * Permite registrar un nuevo tipo de entrada en el sistema. El código debe ser
     * único para evitar duplicados.
     *
     * @OA\Post(
     *     path="/api/admin/entry-types",
     *     tags={"Tipos de Entrada"},
     *     summary="Crear tipo de entrada",
     *     description="Registra un nuevo tipo de entrada en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50, example="Entrada con Suscripción", description="Nombre descriptivo del tipo de entrada"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="SUBSCRIPTION", description="Código único de identificación")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de entrada creado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de entrada creado exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Entrada con Suscripción"),
     *                 @OA\Property(property="code", type="string", example="SUBSCRIPTION"),
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
     *             @OA\Property(property="message", type="string", example="Errores de validación"),
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
    public function store(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:entry_types,code',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear tipo de entrada
        $entryType = EntryType::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return $this->successResponse(
            $entryType,
            'Tipo de entrada creado exitosamente',
            201
        );
    }

    /**
     * Obtener tipo de entrada por ID
     *
     * Consulta los detalles completos de un tipo de entrada específico mediante su
     * identificador único. Retorna un error 404 si no existe.
     *
     * @OA\Get(
     *     path="/api/admin/entry-types/{id}",
     *     tags={"Tipos de Entrada"},
     *     summary="Obtener tipo de entrada",
     *     description="Retorna los detalles de un tipo de entrada específico",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del tipo de entrada",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de entrada encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de entrada encontrado"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Entrada Regular"),
     *                 @OA\Property(property="code", type="string", example="REGULAR"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de entrada no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tipo de entrada no encontrado"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="El tipo de entrada especificado no existe.")
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
    public function show(string $id)
    {
        $entryType = EntryType::find($id);

        if (! $entryType) {
            return $this->errorResponse(
                'Tipo de entrada no encontrado',
                ['id' => ['El tipo de entrada especificado no existe.']],
                404
            );
        }

        return $this->successResponse(
            $entryType,
            'Tipo de entrada encontrado',
            200
        );
    }

    /**
     * Actualizar tipo de entrada existente
     *
     * Modifica los datos de un tipo de entrada específico. Valida que el código
     * sea único (excepto para el registro actual). Retorna error 404 si no existe.
     *
     * @OA\Put(
     *     path="/api/admin/entry-types/{id}",
     *     tags={"Tipos de Entrada"},
     *     summary="Actualizar tipo de entrada",
     *     description="Modifica los datos de un tipo de entrada existente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del tipo de entrada a actualizar",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50, example="Entrada Regular", description="Nombre descriptivo del tipo de entrada"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="REGULAR", description="Código único de identificación")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de entrada actualizado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de entrada actualizado exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Entrada Regular"),
     *                 @OA\Property(property="code", type="string", example="REGULAR"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T14:20:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de entrada no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tipo de entrada no encontrado"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="El tipo de entrada especificado no existe.")
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
     *             @OA\Property(property="message", type="string", example="Errores de validación"),
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
    public function update(Request $request, string $id)
    {
        $entryType = EntryType::find($id);

        if (! $entryType) {
            return $this->errorResponse(
                'Tipo de entrada no encontrado',
                ['id' => ['El tipo de entrada especificado no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:entry_types,code,'.$id,
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar tipo de entrada
        $entryType->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return $this->successResponse(
            $entryType,
            'Tipo de entrada actualizado exitosamente',
            200
        );
    }

    /**
     * Eliminar tipo de entrada
     *
     * Elimina permanentemente un tipo de entrada del sistema. Esta operación
     * no puede deshacerse. Retorna error 404 si el registro no existe.
     *
     * @OA\Delete(
     *     path="/api/admin/entry-types/{id}",
     *     tags={"Tipos de Entrada"},
     *     summary="Eliminar tipo de entrada",
     *     description="Elimina permanentemente un tipo de entrada del sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del tipo de entrada a eliminar",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de entrada eliminado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de entrada eliminado exitosamente"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de entrada no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tipo de entrada no encontrado"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="El tipo de entrada especificado no existe.")
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
    public function destroy(string $id)
    {
        $entryType = EntryType::find($id);

        if (! $entryType) {
            return $this->errorResponse(
                'Tipo de entrada no encontrado',
                ['id' => ['El tipo de entrada especificado no existe.']],
                404
            );
        }

        $entryType->delete();

        return $this->successResponse(
            null,
            'Tipo de entrada eliminado exitosamente',
            200
        );
    }
}
