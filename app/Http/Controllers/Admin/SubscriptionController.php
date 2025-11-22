<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    use ApiResponse;

    /**
     * Listar suscripciones
     *
     * @OA\Get(
     *     path="/api/admin/subscriptions",
     *     tags={"Suscripciones"},
     *     summary="Listar suscripciones",
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
        $subscriptions = Subscription::with(['customer', 'branch', 'vehicleType', 'subscriptionType'])->get();

        return $this->successResponse(
            $subscriptions,
            'Listado de suscripciones',
            200
        );
    }

    /**
     * Crear suscripción
     *
     * @OA\Post(
     *     path="/api/admin/subscriptions",
     *     tags={"Suscripciones"},
     *     summary="Crear suscripción",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"start_date", "end_date", "amount", "customer_id", "branch_id", "vehicle_type_id", "subscription_type_id"},
     *
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="amount", type="number", format="float", example=250000),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="branch_id", type="integer", example=1),
     *             @OA\Property(property="vehicle_type_id", type="integer", example=1),
     *             @OA\Property(property="subscription_type_id", type="integer", example=1)
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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'subscription_type_id' => 'required|exists:subscription_types,id',
        ], [
            'start_date.required' => 'La fecha de inicio es requerida.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'amount.required' => 'El monto es requerido.',
            'amount.numeric' => 'El monto debe ser un valor numérico.',
            'amount.min' => 'El monto debe ser mayor o igual a 0.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'customer_id.required' => 'El cliente es requerido.',
            'customer_id.exists' => 'El cliente no existe.',
            'branch_id.required' => 'La sede es requerida.',
            'branch_id.exists' => 'La sede no existe.',
            'vehicle_type_id.required' => 'El tipo de vehículo es requerido.',
            'vehicle_type_id.exists' => 'El tipo de vehículo no existe.',
            'subscription_type_id.required' => 'El tipo de suscripción es requerido.',
            'subscription_type_id.exists' => 'El tipo de suscripción no existe.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear suscripción
        $subscription = Subscription::create([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'amount' => $request->amount,
            'is_active' => $request->is_active ?? true,
            'customer_id' => $request->customer_id,
            'branch_id' => $request->branch_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'subscription_type_id' => $request->subscription_type_id,
        ]);

        // Cargar relaciones
        $subscription->load(['customer', 'branch', 'vehicleType', 'subscriptionType']);

        return $this->successResponse(
            $subscription,
            'Suscripción creada exitosamente',
            201
        );
    }

    /**
     * Obtener suscripción por ID
     *
     * @OA\Get(
     *     path="/api/admin/subscriptions/{id}",
     *     tags={"Suscripciones"},
     *     summary="Obtener suscripción",
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
        $subscription = Subscription::with(['customer', 'branch', 'vehicleType', 'subscriptionType'])->find($id);

        if (! $subscription) {
            return $this->errorResponse(
                'Suscripción no encontrada',
                ['subscription' => ['La suscripción no existe.']],
                404
            );
        }

        return $this->successResponse(
            $subscription,
            'Suscripción encontrada',
            200
        );
    }

    /**
     * Actualizar suscripción
     *
     * @OA\Put(
     *     path="/api/admin/subscriptions/{id}",
     *     tags={"Suscripciones"},
     *     summary="Actualizar suscripción",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"start_date", "end_date", "amount", "customer_id", "branch_id", "vehicle_type_id", "subscription_type_id"},
     *
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="vehicle_type_id", type="integer"),
     *             @OA\Property(property="subscription_type_id", type="integer")
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
        $subscription = Subscription::find($id);

        if (! $subscription) {
            return $this->errorResponse(
                'Suscripción no encontrada',
                ['subscription' => ['La suscripción no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'subscription_type_id' => 'required|exists:subscription_types,id',
        ], [
            'start_date.required' => 'La fecha de inicio es requerida.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'amount.required' => 'El monto es requerido.',
            'amount.numeric' => 'El monto debe ser un valor numérico.',
            'amount.min' => 'El monto debe ser mayor o igual a 0.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'customer_id.required' => 'El cliente es requerido.',
            'customer_id.exists' => 'El cliente no existe.',
            'branch_id.required' => 'La sede es requerida.',
            'branch_id.exists' => 'La sede no existe.',
            'vehicle_type_id.required' => 'El tipo de vehículo es requerido.',
            'vehicle_type_id.exists' => 'El tipo de vehículo no existe.',
            'subscription_type_id.required' => 'El tipo de suscripción es requerido.',
            'subscription_type_id.exists' => 'El tipo de suscripción no existe.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar suscripción
        $subscription->update([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'amount' => $request->amount,
            'is_active' => $request->is_active ?? $subscription->is_active,
            'customer_id' => $request->customer_id,
            'branch_id' => $request->branch_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'subscription_type_id' => $request->subscription_type_id,
        ]);

        // Cargar relaciones
        $subscription->load(['customer', 'branch', 'vehicleType', 'subscriptionType']);

        return $this->successResponse(
            $subscription,
            'Suscripción actualizada exitosamente',
            200
        );
    }

    /**
     * Eliminar suscripción
     *
     * @OA\Delete(
     *     path="/api/admin/subscriptions/{id}",
     *     tags={"Suscripciones"},
     *     summary="Eliminar suscripción",
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
        $subscription = Subscription::find($id);

        if (! $subscription) {
            return $this->errorResponse(
                'Suscripción no encontrada',
                ['subscription' => ['La suscripción no existe.']],
                404
            );
        }

        $subscription->delete();

        return $this->successResponse(
            null,
            'Suscripción eliminada exitosamente',
            200
        );
    }
}
