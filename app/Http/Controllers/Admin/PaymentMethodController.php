<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    use ApiResponse;

    /**
     * Listar métodos de pago
     *
     * @OA\Get(
     *     path="/api/admin/payment-methods",
     *     tags={"Métodos de Pago"},
     *     summary="Listar métodos de pago",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Listado exitoso"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        return $this->successResponse(
            $paymentMethods,
            'Listado de métodos de pago',
            200
        );
    }

    /**
     * Crear método de pago
     *
     * @OA\Post(
     *     path="/api/admin/payment-methods",
     *     tags={"Métodos de Pago"},
     *     summary="Crear método de pago",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50, example="Efectivo"),
     *             @OA\Property(property="code", type="string", maxLength=20, example="CASH"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
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
            'code' => 'required|string|max:20|unique:payment_methods,code',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear método de pago
        $paymentMethod = PaymentMethod::create([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->is_active ?? true,
        ]);

        return $this->successResponse(
            $paymentMethod,
            'Método de pago creado exitosamente',
            201
        );
    }

    /**
     * Obtener método de pago por ID
     *
     * @OA\Get(
     *     path="/api/admin/payment-methods/{id}",
     *     tags={"Métodos de Pago"},
     *     summary="Obtener método de pago",
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
        $paymentMethod = PaymentMethod::find($id);

        if (! $paymentMethod) {
            return $this->errorResponse(
                'Método de pago no encontrado',
                ['id' => ['El método de pago especificado no existe.']],
                404
            );
        }

        return $this->successResponse(
            $paymentMethod,
            'Método de pago encontrado',
            200
        );
    }

    /**
     * Actualizar método de pago
     *
     * @OA\Put(
     *     path="/api/admin/payment-methods/{id}",
     *     tags={"Métodos de Pago"},
     *     summary="Actualizar método de pago",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *
     *             @OA\Property(property="name", type="string", maxLength=50),
     *             @OA\Property(property="code", type="string", maxLength=20),
     *             @OA\Property(property="is_active", type="boolean")
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
        $paymentMethod = PaymentMethod::find($id);

        if (! $paymentMethod) {
            return $this->errorResponse(
                'Método de pago no encontrado',
                ['id' => ['El método de pago especificado no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:payment_methods,code,'.$id,
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar método de pago
        $paymentMethod->update([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->is_active ?? $paymentMethod->is_active,
        ]);

        return $this->successResponse(
            $paymentMethod,
            'Método de pago actualizado exitosamente',
            200
        );
    }

    /**
     * Eliminar método de pago
     *
     * @OA\Delete(
     *     path="/api/admin/payment-methods/{id}",
     *     tags={"Métodos de Pago"},
     *     summary="Eliminar método de pago",
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
        $paymentMethod = PaymentMethod::find($id);

        if (! $paymentMethod) {
            return $this->errorResponse(
                'Método de pago no encontrado',
                ['id' => ['El método de pago especificado no existe.']],
                404
            );
        }

        $paymentMethod->delete();

        return $this->successResponse(
            null,
            'Método de pago eliminado exitosamente',
            200
        );
    }
}
