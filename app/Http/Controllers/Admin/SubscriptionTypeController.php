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
     * Listar tipos de suscripción
     *
     * @OA\Get(
     *     path="/api/admin/subscription-types",
     *     tags={"Tipos de Suscripción"},
     *     summary="Listar tipos de suscripción",
     *     description="Retorna todos los tipos de suscripción ordenados por duración",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Listado exitoso"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     * Crear tipo de suscripción
     *
     * @OA\Post(
     *     path="/api/admin/subscription-types",
     *     tags={"Tipos de Suscripción"},
     *     summary="Crear tipo de suscripción",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code", "duration_days"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50, example="Mensual"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="MONTHLY"),
     *             @OA\Property(property="duration_days", type="integer", minimum=1, example=30)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Creado exitosamente"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     * Obtener tipo de suscripción por ID
     *
     * @OA\Get(
     *     path="/api/admin/subscription-types/{id}",
     *     tags={"Tipos de Suscripción"},
     *     summary="Obtener tipo de suscripción",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Encontrado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     * Actualizar tipo de suscripción
     *
     * @OA\Put(
     *     path="/api/admin/subscription-types/{id}",
     *     tags={"Tipos de Suscripción"},
     *     summary="Actualizar tipo de suscripción",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code", "duration_days"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50),
     *             @OA\Property(property="code", type="string", maxLength=20),
     *             @OA\Property(property="duration_days", type="integer", minimum=1)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Actualizado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
     * Eliminar tipo de suscripción
     *
     * @OA\Delete(
     *     path="/api/admin/subscription-types/{id}",
     *     tags={"Tipos de Suscripción"},
     *     summary="Eliminar tipo de suscripción",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Eliminado exitosamente"),
     *     @OA\Response(response=404, description="No encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
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
