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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
