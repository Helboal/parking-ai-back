<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentTypeController extends Controller
{
    use ApiResponse;

    /**
     * Listar tipos de documento
     *
     * Obtiene el listado completo de tipos de documento disponibles en el sistema
     * (CC, NIT, CE, Pasaporte, etc.) ordenados alfabéticamente.
     *
     * @OA\Get(
     *     path="/api/admin/document-types",
     *     tags={"Tipos de Documento"},
     *     summary="Listar tipos de documento",
     *     description="Retorna todos los tipos de documento registrados en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listado exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Listado de tipos de documento"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1, description="ID único"),
     *                     @OA\Property(property="name", type="string", example="Cédula de Ciudadanía", description="Nombre del tipo de documento"),
     *                     @OA\Property(property="code", type="string", example="CC", description="Código único"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Documento de identidad", description="Descripción opcional"),
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
        $documentTypes = DocumentType::orderBy('name')->get();

        return $this->successResponse(
            $documentTypes,
            'Listado de tipos de documento',
            200
        );
    }

    /**
     * Crear nuevo tipo de documento
     *
     * Permite registrar un nuevo tipo de documento en el sistema. El código debe ser
     * único para evitar duplicados. Se validan todos los campos antes de la creación.
     *
     * @OA\Post(
     *     path="/api/admin/document-types",
     *     tags={"Tipos de Documento"},
     *     summary="Crear tipo de documento",
     *     description="Registra un nuevo tipo de documento en el sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50, example="Tarjeta de Identidad", description="Nombre descriptivo del tipo de documento"),
     *             @OA\Property(property="code", type="string", maxLength=10, example="TI", description="Código único de identificación (debe ser único en el sistema)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de documento creado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de documento creado exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="name", type="string", example="Tarjeta de Identidad"),
     *                 @OA\Property(property="code", type="string", example="TI"),
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
            'code' => 'required|string|max:10|unique:document_types,code',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear tipo de documento
        $documentType = DocumentType::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return $this->successResponse(
            $documentType,
            'Tipo de documento creado exitosamente',
            201
        );
    }

    /**
     * Obtener tipo de documento por ID
     *
     * Consulta los detalles completos de un tipo de documento específico mediante su
     * identificador único. Retorna un error 404 si no existe.
     *
     * @OA\Get(
     *     path="/api/admin/document-types/{id}",
     *     tags={"Tipos de Documento"},
     *     summary="Obtener tipo de documento",
     *     description="Retorna los detalles de un tipo de documento específico",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del tipo de documento",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de documento encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de documento encontrado"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Cédula de Ciudadanía"),
     *                 @OA\Property(property="code", type="string", example="CC"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Documento de identidad"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de documento no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tipo de documento no encontrado"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="El tipo de documento especificado no existe.")
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
        $documentType = DocumentType::find($id);

        if (! $documentType) {
            return $this->errorResponse(
                'Tipo de documento no encontrado',
                ['id' => ['El tipo de documento especificado no existe.']],
                404
            );
        }

        return $this->successResponse(
            $documentType,
            'Tipo de documento encontrado',
            200
        );
    }

    /**
     * Actualizar tipo de documento existente
     *
     * Modifica los datos de un tipo de documento específico. Valida que el código
     * sea único (excepto para el registro actual). Retorna error 404 si no existe.
     *
     * @OA\Put(
     *     path="/api/admin/document-types/{id}",
     *     tags={"Tipos de Documento"},
     *     summary="Actualizar tipo de documento",
     *     description="Modifica los datos de un tipo de documento existente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del tipo de documento a actualizar",
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
     *             @OA\Property(property="name", type="string", maxLength=50, example="Cédula de Ciudadanía", description="Nombre descriptivo del tipo de documento"),
     *             @OA\Property(property="code", type="string", maxLength=10, example="CC", description="Código único de identificación")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de documento actualizado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de documento actualizado exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Cédula de Ciudadanía"),
     *                 @OA\Property(property="code", type="string", example="CC"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T14:20:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de documento no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tipo de documento no encontrado"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="El tipo de documento especificado no existe.")
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
        $documentType = DocumentType::find($id);

        if (! $documentType) {
            return $this->errorResponse(
                'Tipo de documento no encontrado',
                ['id' => ['El tipo de documento especificado no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:10|unique:document_types,code,'.$id,
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar tipo de documento
        $documentType->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return $this->successResponse(
            $documentType,
            'Tipo de documento actualizado exitosamente',
            200
        );
    }

    /**
     * Eliminar tipo de documento
     *
     * Elimina permanentemente un tipo de documento del sistema. Esta operación
     * no puede deshacerse. Retorna error 404 si el registro no existe.
     *
     * @OA\Delete(
     *     path="/api/admin/document-types/{id}",
     *     tags={"Tipos de Documento"},
     *     summary="Eliminar tipo de documento",
     *     description="Elimina permanentemente un tipo de documento del sistema",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del tipo de documento a eliminar",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de documento eliminado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tipo de documento eliminado exitosamente"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Tipo de documento no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tipo de documento no encontrado"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="El tipo de documento especificado no existe.")
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
        $documentType = DocumentType::find($id);

        if (! $documentType) {
            return $this->errorResponse(
                'Tipo de documento no encontrado',
                ['id' => ['El tipo de documento especificado no existe.']],
                404
            );
        }

        $documentType->delete();

        return $this->successResponse(
            null,
            'Tipo de documento eliminado exitosamente',
            200
        );
    }
}
