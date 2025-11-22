<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Gestión de pagos del sistema"
 * )
 */
class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/admin/payments",
     *     tags={"Payments"},
     *     summary="Listar todos los pagos",
     *     description="Obtiene la lista completa de pagos con sus relaciones",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de pagos obtenida exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pagos obtenidos exitosamente"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $payments = Payment::with(['invoice', 'subscription', 'paymentMethod', 'user'])->get();

        return $this->successResponse($payments, 'Pagos obtenidos exitosamente', 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/payments",
     *     tags={"Payments"},
     *     summary="Crear un nuevo pago",
     *     description="Crea un nuevo registro de pago para una factura o suscripción. El monto se calcula automáticamente según el balance pendiente de la factura o el monto de la suscripción. El pago se registra automáticamente como completado con la fecha/hora actual y el usuario autenticado.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"payment_method_id"},
     *
     *             @OA\Property(property="invoice_id", type="integer", example=1, description="ID de la factura a pagar (debe proporcionar invoice_id O subscription_id, no ambos)"),
     *             @OA\Property(property="subscription_id", type="integer", example=null, description="ID de la suscripción a pagar (debe proporcionar invoice_id O subscription_id, no ambos)"),
     *             @OA\Property(property="payment_method_id", type="integer", example=1, description="ID del método de pago"),
     *             @OA\Property(property="reference_number", type="string", example="TRX-123456", description="Número de referencia de la transacción"),
     *             @OA\Property(property="notes", type="string", example="Pago en efectivo", description="Notas adicionales")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Pago creado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pago creado exitosamente"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="code", type="integer", example=201)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errores de validación"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="code", type="integer", example=422)
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'nullable|exists:invoices,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Errores de validación', $validator->errors(), 422);
        }

        // LÓGICA DE NEGOCIO: Validar que al menos uno de invoice_id o subscription_id esté presente
        if (empty($request->invoice_id) && empty($request->subscription_id)) {
            return $this->errorResponse(
                'Errores de validación',
                ['invoice_or_subscription' => ['Debe proporcionar al menos un invoice_id o subscription_id']],
                422
            );
        }

        // LÓGICA DE NEGOCIO: Validar que no se proporcionen ambos
        if (! empty($request->invoice_id) && ! empty($request->subscription_id)) {
            return $this->errorResponse(
                'Errores de validación',
                ['invoice_or_subscription' => ['Solo puede proporcionar invoice_id O subscription_id, no ambos']],
                422
            );
        }

        // LÓGICA DE NEGOCIO: Determinar el monto según el tipo de pago
        $amount = 0;
        $paymentFor = '';

        if ($request->invoice_id) {
            $invoice = \App\Models\Invoice::find($request->invoice_id);

            // Verificar si ya tiene pagos
            $totalPaid = \App\Models\Payment::where('invoice_id', $invoice->id)
                ->where('status', 'completed')
                ->sum('amount');

            if ($totalPaid >= $invoice->total) {
                return $this->errorResponse(
                    'La factura ya está pagada',
                    ['invoice' => ['Esta factura ya ha sido pagada completamente']],
                    422
                );
            }

            $amount = $invoice->total - $totalPaid;
            $paymentFor = 'factura #'.$invoice->id;
        }

        if ($request->subscription_id) {
            $subscription = \App\Models\Subscription::find($request->subscription_id);

            if (! $subscription->is_active) {
                return $this->errorResponse(
                    'La suscripción no está activa',
                    ['subscription' => ['No se puede pagar una suscripción inactiva']],
                    422
                );
            }

            $amount = $subscription->amount;
            $paymentFor = 'suscripción #'.$subscription->id;
        }

        // Crear el pago
        $payment = Payment::create([
            'amount' => $amount,
            'payment_datetime' => now(),
            'status' => 'completed',
            'reference_number' => $request->reference_number,
            'invoice_id' => $request->invoice_id,
            'subscription_id' => $request->subscription_id,
            'payment_method_id' => $request->payment_method_id,
            'user_id' => auth()->id(),
            'notes' => $request->notes,
        ]);

        $payment->load(['invoice', 'subscription', 'paymentMethod', 'user']);

        return $this->successResponse(
            $payment,
            'Pago de $'.number_format($amount, 2)." registrado exitosamente para $paymentFor",
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/admin/payments/{id}",
     *     tags={"Payments"},
     *     summary="Obtener un pago específico",
     *     description="Obtiene los detalles de un pago por su ID",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del pago",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pago obtenido exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pago obtenido exitosamente"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Pago no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Pago no encontrado"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="array", @OA\Items()),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        $payment = Payment::with(['invoice', 'subscription', 'paymentMethod', 'user'])->find($id);

        if (! $payment) {
            return $this->errorResponse('Pago no encontrado', [], 404);
        }

        return $this->successResponse($payment, 'Pago obtenido exitosamente', 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/payments/{id}",
     *     tags={"Payments"},
     *     summary="Actualizar un pago",
     *     description="Actualiza los datos de un pago existente",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del pago",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="amount", type="number", format="decimal", example=50000.00),
     *             @OA\Property(property="payment_datetime", type="string", format="date-time", example="2024-01-15 14:30:00"),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "refunded"}, example="completed"),
     *             @OA\Property(property="reference_number", type="string", example="TRX-123456"),
     *             @OA\Property(property="invoice_id", type="integer", example=1),
     *             @OA\Property(property="subscription_id", type="integer", example=null),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Pago modificado")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pago actualizado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pago actualizado exitosamente"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Pago no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Pago no encontrado"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="array", @OA\Items()),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errores de validación"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="code", type="integer", example=422)
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (! $payment) {
            return $this->errorResponse('Pago no encontrado', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_datetime' => 'required|date',
            'status' => 'required|in:pending,completed,failed,refunded',
            'reference_number' => 'nullable|string|max:100',
            'invoice_id' => 'nullable|exists:invoices,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Errores de validación', $validator->errors(), 422);
        }

        // Validar que al menos uno de invoice_id o subscription_id esté presente
        if (empty($request->invoice_id) && empty($request->subscription_id)) {
            return $this->errorResponse(
                'Errores de validación',
                ['invoice_or_subscription' => ['Debe proporcionar al menos un invoice_id o subscription_id']],
                422
            );
        }

        $payment->update($validator->validated());
        $payment->load(['invoice', 'subscription', 'paymentMethod', 'user']);

        return $this->successResponse($payment, 'Pago actualizado exitosamente', 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/payments/{id}",
     *     tags={"Payments"},
     *     summary="Eliminar un pago",
     *     description="Elimina un pago del sistema",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del pago",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pago eliminado exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pago eliminado exitosamente"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Pago no encontrado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Pago no encontrado"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="array", @OA\Items()),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (! $payment) {
            return $this->errorResponse('Pago no encontrado', [], 404);
        }

        $payment->delete();

        return $this->successResponse(null, 'Pago eliminado exitosamente', 200);
    }
}
