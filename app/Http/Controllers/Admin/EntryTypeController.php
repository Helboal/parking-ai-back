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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
