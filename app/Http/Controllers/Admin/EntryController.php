<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entry;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EntryController extends Controller
{
    use ApiResponse;

    /**
     * Listar entradas
     *
     * @OA\Get(
     *     path="/api/admin/entries",
     *     tags={"Entradas"},
     *     summary="Listar entradas",
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
        $entries = Entry::with(['entryUser', 'exitUser', 'branch', 'vehicle', 'entryType', 'subscription'])->get();

        return $this->successResponse(
            $entries,
            'Listado de entradas',
            200
        );
    }

    /**
     * Crear entrada
     *
     * @OA\Post(
     *     path="/api/admin/entries",
     *     tags={"Entradas"},
     *     summary="Crear entrada",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"entry_datetime", "status", "branch_id", "vehicle_id", "entry_type_id"},
     *
     *             @OA\Property(property="entry_datetime", type="string", format="date-time", example="2025-01-21 08:00:00"),
     *             @OA\Property(property="exit_datetime", type="string", format="date-time", example="2025-01-21 18:00:00"),
     *             @OA\Property(property="total_minutes", type="integer", example=600),
     *             @OA\Property(property="status", type="string", enum={"active", "completed", "cancelled"}, example="active"),
     *             @OA\Property(property="entry_user_id", type="integer", example=1),
     *             @OA\Property(property="exit_user_id", type="integer", example=2),
     *             @OA\Property(property="branch_id", type="integer", example=1),
     *             @OA\Property(property="vehicle_id", type="integer", example=1),
     *             @OA\Property(property="entry_type_id", type="integer", example=1),
     *             @OA\Property(property="subscription_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Cliente frecuente")
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
        // Validación
        $validator = Validator::make($request->all(), [
            'entry_datetime' => 'required|date',
            'exit_datetime' => 'nullable|date|after_or_equal:entry_datetime',
            'total_minutes' => 'nullable|integer|min:0',
            'status' => 'required|in:active,completed,cancelled',
            'entry_user_id' => 'nullable|exists:users,id',
            'exit_user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'entry_type_id' => 'required|exists:entry_types,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'notes' => 'nullable|string',
        ], [
            'entry_datetime.required' => 'La fecha y hora de entrada es requerida.',
            'entry_datetime.date' => 'La fecha y hora de entrada debe ser una fecha válida.',
            'exit_datetime.date' => 'La fecha y hora de salida debe ser una fecha válida.',
            'exit_datetime.after_or_equal' => 'La fecha y hora de salida debe ser igual o posterior a la fecha de entrada.',
            'total_minutes.integer' => 'El total de minutos debe ser un número entero.',
            'total_minutes.min' => 'El total de minutos debe ser mayor o igual a 0.',
            'status.required' => 'El estado es requerido.',
            'status.in' => 'El estado debe ser: active, completed o cancelled.',
            'entry_user_id.exists' => 'El usuario de entrada no existe.',
            'exit_user_id.exists' => 'El usuario de salida no existe.',
            'branch_id.required' => 'La sede es requerida.',
            'branch_id.exists' => 'La sede no existe.',
            'vehicle_id.required' => 'El vehículo es requerido.',
            'vehicle_id.exists' => 'El vehículo no existe.',
            'entry_type_id.required' => 'El tipo de entrada es requerido.',
            'entry_type_id.exists' => 'El tipo de entrada no existe.',
            'subscription_id.exists' => 'La suscripción no existe.',
            'notes.string' => 'Las notas deben ser texto.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear entrada
        $entry = Entry::create([
            'entry_datetime' => $request->entry_datetime,
            'exit_datetime' => $request->exit_datetime,
            'total_minutes' => $request->total_minutes,
            'status' => $request->status,
            'entry_user_id' => $request->entry_user_id,
            'exit_user_id' => $request->exit_user_id,
            'branch_id' => $request->branch_id,
            'vehicle_id' => $request->vehicle_id,
            'entry_type_id' => $request->entry_type_id,
            'subscription_id' => $request->subscription_id,
            'notes' => $request->notes,
        ]);

        // Cargar relaciones
        $entry->load(['entryUser', 'exitUser', 'branch', 'vehicle', 'entryType', 'subscription']);

        return $this->successResponse(
            $entry,
            'Entrada creada exitosamente',
            201
        );
    }

    /**
     * Obtener entrada por ID
     *
     * @OA\Get(
     *     path="/api/admin/entries/{id}",
     *     tags={"Entradas"},
     *     summary="Obtener entrada",
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
        $entry = Entry::with(['entryUser', 'exitUser', 'branch', 'vehicle', 'entryType', 'subscription'])->find($id);

        if (! $entry) {
            return $this->errorResponse(
                'Entrada no encontrada',
                ['entry' => ['La entrada no existe.']],
                404
            );
        }

        return $this->successResponse(
            $entry,
            'Entrada encontrada',
            200
        );
    }

    /**
     * Actualizar entrada
     *
     * @OA\Put(
     *     path="/api/admin/entries/{id}",
     *     tags={"Entradas"},
     *     summary="Actualizar entrada",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"entry_datetime", "status", "branch_id", "vehicle_id", "entry_type_id"},
     *
     *             @OA\Property(property="entry_datetime", type="string", format="date-time"),
     *             @OA\Property(property="exit_datetime", type="string", format="date-time"),
     *             @OA\Property(property="total_minutes", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active", "completed", "cancelled"}),
     *             @OA\Property(property="entry_user_id", type="integer"),
     *             @OA\Property(property="exit_user_id", type="integer"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="vehicle_id", type="integer"),
     *             @OA\Property(property="entry_type_id", type="integer"),
     *             @OA\Property(property="subscription_id", type="integer"),
     *             @OA\Property(property="notes", type="string")
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
        $entry = Entry::find($id);

        if (! $entry) {
            return $this->errorResponse(
                'Entrada no encontrada',
                ['entry' => ['La entrada no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'entry_datetime' => 'required|date',
            'exit_datetime' => 'nullable|date|after_or_equal:entry_datetime',
            'total_minutes' => 'nullable|integer|min:0',
            'status' => 'required|in:active,completed,cancelled',
            'entry_user_id' => 'nullable|exists:users,id',
            'exit_user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'entry_type_id' => 'required|exists:entry_types,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'notes' => 'nullable|string',
        ], [
            'entry_datetime.required' => 'La fecha y hora de entrada es requerida.',
            'entry_datetime.date' => 'La fecha y hora de entrada debe ser una fecha válida.',
            'exit_datetime.date' => 'La fecha y hora de salida debe ser una fecha válida.',
            'exit_datetime.after_or_equal' => 'La fecha y hora de salida debe ser igual o posterior a la fecha de entrada.',
            'total_minutes.integer' => 'El total de minutos debe ser un número entero.',
            'total_minutes.min' => 'El total de minutos debe ser mayor o igual a 0.',
            'status.required' => 'El estado es requerido.',
            'status.in' => 'El estado debe ser: active, completed o cancelled.',
            'entry_user_id.exists' => 'El usuario de entrada no existe.',
            'exit_user_id.exists' => 'El usuario de salida no existe.',
            'branch_id.required' => 'La sede es requerida.',
            'branch_id.exists' => 'La sede no existe.',
            'vehicle_id.required' => 'El vehículo es requerido.',
            'vehicle_id.exists' => 'El vehículo no existe.',
            'entry_type_id.required' => 'El tipo de entrada es requerido.',
            'entry_type_id.exists' => 'El tipo de entrada no existe.',
            'subscription_id.exists' => 'La suscripción no existe.',
            'notes.string' => 'Las notas deben ser texto.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar entrada
        $entry->update([
            'entry_datetime' => $request->entry_datetime,
            'exit_datetime' => $request->exit_datetime,
            'total_minutes' => $request->total_minutes,
            'status' => $request->status,
            'entry_user_id' => $request->entry_user_id,
            'exit_user_id' => $request->exit_user_id,
            'branch_id' => $request->branch_id,
            'vehicle_id' => $request->vehicle_id,
            'entry_type_id' => $request->entry_type_id,
            'subscription_id' => $request->subscription_id,
            'notes' => $request->notes,
        ]);

        // Cargar relaciones
        $entry->load(['entryUser', 'exitUser', 'branch', 'vehicle', 'entryType', 'subscription']);

        return $this->successResponse(
            $entry,
            'Entrada actualizada exitosamente',
            200
        );
    }

    /**
     * Eliminar entrada
     *
     * @OA\Delete(
     *     path="/api/admin/entries/{id}",
     *     tags={"Entradas"},
     *     summary="Eliminar entrada",
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
        $entry = Entry::find($id);

        if (! $entry) {
            return $this->errorResponse(
                'Entrada no encontrada',
                ['entry' => ['La entrada no existe.']],
                404
            );
        }

        $entry->delete();

        return $this->successResponse(
            null,
            'Entrada eliminada exitosamente',
            200
        );
    }
}
