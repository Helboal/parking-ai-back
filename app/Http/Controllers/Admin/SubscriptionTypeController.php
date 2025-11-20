<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionTypeController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptionTypes = SubscriptionType::orderBy('duration_days')->get();

        return $this->successResponse(
            $subscriptionTypes,
            'Listado de tipos de suscripción',
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
            'code' => 'required|string|max:20|unique:subscription_types,code',
            'duration_days' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear tipo de suscripción
        $subscriptionType = SubscriptionType::create([
            'name' => $request->name,
            'code' => $request->code,
            'duration_days' => $request->duration_days,
        ]);

        return $this->successResponse(
            $subscriptionType,
            'Tipo de suscripción creado exitosamente',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $subscriptionType = SubscriptionType::find($id);

        if (! $subscriptionType) {
            return $this->errorResponse(
                'Tipo de suscripción no encontrado',
                ['id' => ['El tipo de suscripción especificado no existe.']],
                404
            );
        }

        return $this->successResponse(
            $subscriptionType,
            'Tipo de suscripción encontrado',
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subscriptionType = SubscriptionType::find($id);

        if (! $subscriptionType) {
            return $this->errorResponse(
                'Tipo de suscripción no encontrado',
                ['id' => ['El tipo de suscripción especificado no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:subscription_types,code,'.$id,
            'duration_days' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar tipo de suscripción
        $subscriptionType->update([
            'name' => $request->name,
            'code' => $request->code,
            'duration_days' => $request->duration_days,
        ]);

        return $this->successResponse(
            $subscriptionType,
            'Tipo de suscripción actualizado exitosamente',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subscriptionType = SubscriptionType::find($id);

        if (! $subscriptionType) {
            return $this->errorResponse(
                'Tipo de suscripción no encontrado',
                ['id' => ['El tipo de suscripción especificado no existe.']],
                404
            );
        }

        $subscriptionType->delete();

        return $this->successResponse(
            null,
            'Tipo de suscripción eliminado exitosamente',
            200
        );
    }
}
