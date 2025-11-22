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
            'branch_id' => 'required|exists:branches,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'entry_type_id' => 'required|exists:entry_types,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'notes' => 'nullable|string',
        ], [
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

        // LÓGICA DE NEGOCIO: Verificar que el vehículo no esté actualmente en el parqueadero
        $activeEntry = Entry::where('vehicle_id', $request->vehicle_id)
            ->where('status', 'active')
            ->first();

        if ($activeEntry) {
            return $this->errorResponse(
                'El vehículo ya se encuentra en el parqueadero',
                ['vehicle' => ['Este vehículo ya tiene una entrada activa. Debe registrar la salida primero.']],
                422
            );
        }

        // LÓGICA DE NEGOCIO: Verificar capacidad disponible
        $vehicle = \App\Models\Vehicle::with('vehicleType')->find($request->vehicle_id);
        $capacity = \App\Models\BranchParkingCapacity::where('branch_id', $request->branch_id)
            ->where('vehicle_type_id', $vehicle->vehicle_type_id)
            ->first();

        if (!$capacity) {
            return $this->errorResponse(
                'No hay configuración de capacidad para este tipo de vehículo en esta sede',
                ['capacity' => ['La sede no tiene configurada capacidad para este tipo de vehículo.']],
                422
            );
        }

        if ($capacity->occupied_spaces >= $capacity->total_spaces) {
            return $this->errorResponse(
                'No hay cupos disponibles',
                ['capacity' => ['No hay espacios disponibles para este tipo de vehículo en esta sede.']],
                422
            );
        }

        // LÓGICA DE NEGOCIO: Si es entrada por suscripción, validar que esté activa
        if ($request->subscription_id) {
            $subscription = \App\Models\Subscription::find($request->subscription_id);
            if (!$subscription->is_active) {
                return $this->errorResponse(
                    'La suscripción no está activa',
                    ['subscription' => ['La suscripción no está activa o ha expirado.']],
                    422
                );
            }

            $today = now()->toDateString();
            if ($today < $subscription->start_date || $today > $subscription->end_date) {
                return $this->errorResponse(
                    'La suscripción no está vigente',
                    ['subscription' => ['La suscripción no está dentro del período de vigencia.']],
                    422
                );
            }
        }

        // Crear entrada (solo datos de entrada, sin salida)
        $entry = Entry::create([
            'entry_datetime' => now(),
            'exit_datetime' => null,
            'total_minutes' => null,
            'status' => 'active',
            'entry_user_id' => auth()->id(),
            'exit_user_id' => null,
            'branch_id' => $request->branch_id,
            'vehicle_id' => $request->vehicle_id,
            'entry_type_id' => $request->entry_type_id,
            'subscription_id' => $request->subscription_id,
            'notes' => $request->notes,
        ]);

        // LÓGICA DE NEGOCIO: Decrementar cupos disponibles
        $capacity->increment('occupied_spaces');

        // Cargar relaciones
        $entry->load(['entryUser', 'branch', 'vehicle.vehicleType', 'vehicle.customer', 'entryType', 'subscription']);

        return $this->successResponse(
            $entry,
            'Entrada registrada exitosamente. Cupos disponibles: ' . ($capacity->total_spaces - $capacity->occupied_spaces),
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

    /**
     * Registrar salida de vehículo y generar factura automáticamente
     *
     * @OA\Post(
     *     path="/api/admin/entries/{id}/exit",
     *     tags={"Entradas"},
     *     summary="Registrar salida de vehículo",
     *     description="Registra la salida del vehículo, calcula la tarifa y genera la factura automáticamente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string", example="Salida sin novedad")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Salida registrada exitosamente con factura"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerExit(Request $request, $id)
    {
        $entry = Entry::with(['vehicle.vehicleType', 'branch', 'subscription'])->find($id);

        if (!$entry) {
            return $this->errorResponse(
                'Entrada no encontrada',
                ['entry' => ['La entrada no existe.']],
                404
            );
        }

        // Validar que la entrada esté activa
        if ($entry->status !== 'active') {
            return $this->errorResponse(
                'La entrada no está activa',
                ['entry' => ['Solo se puede registrar salida de entradas activas.']],
                422
            );
        }

        // Registrar salida
        $exitDatetime = now();
        $totalMinutes = $entry->entry_datetime->diffInMinutes($exitDatetime);

        $entry->update([
            'exit_datetime' => $exitDatetime,
            'total_minutes' => $totalMinutes,
            'status' => 'completed',
            'exit_user_id' => auth()->id(),
            'notes' => $request->notes ?? $entry->notes,
        ]);

        // LÓGICA DE NEGOCIO: Liberar cupo
        $capacity = \App\Models\BranchParkingCapacity::where('branch_id', $entry->branch_id)
            ->where('vehicle_type_id', $entry->vehicle->vehicle_type_id)
            ->first();

        if ($capacity) {
            $capacity->decrement('occupied_spaces');
        }

        // LÓGICA DE NEGOCIO: Si es entrada por suscripción, NO generar factura
        if ($entry->subscription_id) {
            $entry->load(['entryUser', 'exitUser', 'branch', 'vehicle.customer', 'subscription']);

            return $this->successResponse(
                $entry,
                'Salida registrada exitosamente. Entrada por suscripción, no se genera factura.',
                200
            );
        }

        // LÓGICA DE NEGOCIO: Generar factura automáticamente
        // 1. Obtener tarifa por minuto de la sede para este tipo de vehículo
        $branchRate = \App\Models\BranchRate::where('branch_id', $entry->branch_id)
            ->where('vehicle_type_id', $entry->vehicle->vehicle_type_id)
            ->where('is_active', true)
            ->first();

        if (!$branchRate) {
            return $this->errorResponse(
                'No hay tarifa configurada',
                ['rate' => ['No hay tarifa configurada para este tipo de vehículo en esta sede.']],
                422
            );
        }

        $ratePerMinute = $branchRate->rate_per_minute;

        // 2. Calcular subtotal base
        $subtotal = $ratePerMinute * $totalMinutes;

        // 3. Verificar si aplica tarifa plana
        $flatRateApplied = false;
        $branchFlatRate = \App\Models\BranchFlatRate::where('branch_id', $entry->branch_id)
            ->where('vehicle_type_id', $entry->vehicle->vehicle_type_id)
            ->where('is_active', true)
            ->where('minutes_threshold', '<=', $totalMinutes)
            ->orderBy('minutes_threshold', 'desc')
            ->first();

        if ($branchFlatRate) {
            $subtotal = $branchFlatRate->flat_rate_amount;
            $flatRateApplied = true;
        }

        // 4. Aplicar descuentos por tiempo
        $discountPercentage = 0;
        $branchDiscount = \App\Models\BranchDiscount::where('branch_id', $entry->branch_id)
            ->where('vehicle_type_id', $entry->vehicle->vehicle_type_id)
            ->where('is_active', true)
            ->where('minutes', '<=', $totalMinutes)
            ->orderBy('discount_percentage', 'desc')
            ->first();

        if ($branchDiscount) {
            $discountPercentage = $branchDiscount->discount_percentage;
        }

        $discountAmount = ($subtotal * $discountPercentage) / 100;

        // 5. Calcular base imponible (subtotal - descuento)
        $taxableAmount = $subtotal - $discountAmount;

        // 6. Obtener impuestos de la sede
        $branchTaxes = \App\Models\BranchTax::where('branch_id', $entry->branch_id)
            ->where('is_active', true)
            ->with('tax')
            ->get();

        $totalTaxAmount = 0;
        $taxesDetail = [];

        foreach ($branchTaxes as $branchTax) {
            if ($branchTax->tax && $branchTax->tax->is_active) {
                $taxAmount = ($taxableAmount * $branchTax->tax->percentage) / 100;
                $totalTaxAmount += $taxAmount;

                $taxesDetail[] = [
                    'tax_id' => $branchTax->tax->id,
                    'tax_percentage' => $branchTax->tax->percentage,
                    'tax_amount' => round($taxAmount, 2),
                ];
            }
        }

        // 7. Calcular total final
        $total = $taxableAmount + $totalTaxAmount;

        // 8. Crear factura
        $invoice = \App\Models\Invoice::create([
            'rate_per_minute' => $ratePerMinute,
            'discount_percentage' => $discountPercentage,
            'flat_rate_applied' => $flatRateApplied,
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($totalTaxAmount, 2),
            'total' => round($total, 2),
            'entry_id' => $entry->id,
        ]);

        // 9. Crear detalle de impuestos
        foreach ($taxesDetail as $taxDetail) {
            \App\Models\InvoiceTax::create([
                'invoice_id' => $invoice->id,
                'tax_id' => $taxDetail['tax_id'],
                'tax_percentage' => $taxDetail['tax_percentage'],
                'tax_amount' => $taxDetail['tax_amount'],
            ]);
        }

        // Cargar todas las relaciones
        $entry->load([
            'entryUser',
            'exitUser',
            'branch',
            'vehicle.customer',
            'vehicle.vehicleType',
            'invoice.invoiceTaxes.tax'
        ]);

        return $this->successResponse(
            [
                'entry' => $entry,
                'invoice' => $invoice->load('invoiceTaxes.tax'),
            ],
            'Salida registrada exitosamente. Factura generada automáticamente.',
            200
        );
    }
}
