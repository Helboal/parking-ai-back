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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
