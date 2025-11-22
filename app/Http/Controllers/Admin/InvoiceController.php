<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    use ApiResponse;

    /**
     * Listar facturas
     *
     * @OA\Get(
     *     path="/api/admin/invoices",
     *     tags={"Facturas"},
     *     summary="Listar facturas",
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
        $invoices = Invoice::with([
            'entry.vehicle',
            'entry.branch',
            'invoiceTaxes.tax',
        ])->get();

        return $this->successResponse(
            $invoices,
            'Listado de facturas',
            200
        );
    }

    /**
     * Crear factura
     *
     * @OA\Post(
     *     path="/api/admin/invoices",
     *     tags={"Facturas"},
     *     summary="Crear factura",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"rate_per_minute", "flat_rate_applied", "subtotal", "total", "entry_id"},
     *
     *             @OA\Property(property="rate_per_minute", type="number", format="float", minimum=0, example=100.00),
     *             @OA\Property(property="discount_percentage", type="integer", minimum=0, maximum=100, example=10),
     *             @OA\Property(property="flat_rate_applied", type="boolean", example=false),
     *             @OA\Property(property="subtotal", type="number", format="float", minimum=0, example=5000.00),
     *             @OA\Property(property="discount_amount", type="number", format="float", minimum=0, example=500.00),
     *             @OA\Property(property="tax_amount", type="number", format="float", minimum=0, example=855.00),
     *             @OA\Property(property="total", type="number", format="float", minimum=0, example=5355.00),
     *             @OA\Property(property="entry_id", type="integer", example=1)
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
            'rate_per_minute' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'flat_rate_applied' => 'boolean',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'entry_id' => 'required|exists:entries,id|unique:invoices,entry_id',
        ], [
            'rate_per_minute.required' => 'La tarifa por minuto es requerida.',
            'rate_per_minute.numeric' => 'La tarifa por minuto debe ser un número.',
            'rate_per_minute.min' => 'La tarifa por minuto debe ser mayor o igual a 0.',
            'discount_percentage.integer' => 'El porcentaje de descuento debe ser un número entero.',
            'discount_percentage.min' => 'El porcentaje de descuento debe ser mayor o igual a 0.',
            'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
            'flat_rate_applied.boolean' => 'El campo de tarifa plana aplicada debe ser verdadero o falso.',
            'subtotal.required' => 'El subtotal es requerido.',
            'subtotal.numeric' => 'El subtotal debe ser un número.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'discount_amount.numeric' => 'El monto del descuento debe ser un número.',
            'discount_amount.min' => 'El monto del descuento debe ser mayor o igual a 0.',
            'tax_amount.numeric' => 'El monto del impuesto debe ser un número.',
            'tax_amount.min' => 'El monto del impuesto debe ser mayor o igual a 0.',
            'total.required' => 'El total es requerido.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
            'entry_id.required' => 'La entrada es requerida.',
            'entry_id.exists' => 'La entrada no existe.',
            'entry_id.unique' => 'Ya existe una factura para esta entrada.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear factura
        $invoice = Invoice::create([
            'rate_per_minute' => $request->rate_per_minute,
            'discount_percentage' => $request->discount_percentage,
            'flat_rate_applied' => $request->flat_rate_applied ?? false,
            'subtotal' => $request->subtotal,
            'discount_amount' => $request->discount_amount,
            'tax_amount' => $request->tax_amount,
            'total' => $request->total,
            'entry_id' => $request->entry_id,
        ]);

        // Cargar relaciones
        $invoice->load([
            'entry.vehicle',
            'entry.branch',
            'invoiceTaxes.tax',
        ]);

        return $this->successResponse(
            $invoice,
            'Factura creada exitosamente',
            201
        );
    }

    /**
     * Obtener factura por ID
     *
     * @OA\Get(
     *     path="/api/admin/invoices/{id}",
     *     tags={"Facturas"},
     *     summary="Obtener factura",
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
        $invoice = Invoice::with([
            'entry.vehicle',
            'entry.branch',
            'invoiceTaxes.tax',
        ])->find($id);

        if (! $invoice) {
            return $this->errorResponse(
                'Factura no encontrada',
                ['invoice' => ['La factura no existe.']],
                404
            );
        }

        return $this->successResponse(
            $invoice,
            'Factura encontrada',
            200
        );
    }

    /**
     * Actualizar factura
     *
     * @OA\Put(
     *     path="/api/admin/invoices/{id}",
     *     tags={"Facturas"},
     *     summary="Actualizar factura",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"rate_per_minute", "flat_rate_applied", "subtotal", "total", "entry_id"},
     *
     *             @OA\Property(property="rate_per_minute", type="number", format="float", minimum=0),
     *             @OA\Property(property="discount_percentage", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="flat_rate_applied", type="boolean"),
     *             @OA\Property(property="subtotal", type="number", format="float", minimum=0),
     *             @OA\Property(property="discount_amount", type="number", format="float", minimum=0),
     *             @OA\Property(property="tax_amount", type="number", format="float", minimum=0),
     *             @OA\Property(property="total", type="number", format="float", minimum=0),
     *             @OA\Property(property="entry_id", type="integer")
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
        $invoice = Invoice::find($id);

        if (! $invoice) {
            return $this->errorResponse(
                'Factura no encontrada',
                ['invoice' => ['La factura no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'rate_per_minute' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'flat_rate_applied' => 'boolean',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'entry_id' => [
                'required',
                'exists:entries,id',
                Rule::unique('invoices')->ignore($invoice->id),
            ],
        ], [
            'rate_per_minute.required' => 'La tarifa por minuto es requerida.',
            'rate_per_minute.numeric' => 'La tarifa por minuto debe ser un número.',
            'rate_per_minute.min' => 'La tarifa por minuto debe ser mayor o igual a 0.',
            'discount_percentage.integer' => 'El porcentaje de descuento debe ser un número entero.',
            'discount_percentage.min' => 'El porcentaje de descuento debe ser mayor o igual a 0.',
            'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
            'flat_rate_applied.boolean' => 'El campo de tarifa plana aplicada debe ser verdadero o falso.',
            'subtotal.required' => 'El subtotal es requerido.',
            'subtotal.numeric' => 'El subtotal debe ser un número.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'discount_amount.numeric' => 'El monto del descuento debe ser un número.',
            'discount_amount.min' => 'El monto del descuento debe ser mayor o igual a 0.',
            'tax_amount.numeric' => 'El monto del impuesto debe ser un número.',
            'tax_amount.min' => 'El monto del impuesto debe ser mayor o igual a 0.',
            'total.required' => 'El total es requerido.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
            'entry_id.required' => 'La entrada es requerida.',
            'entry_id.exists' => 'La entrada no existe.',
            'entry_id.unique' => 'Ya existe una factura para esta entrada.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar factura
        $invoice->update([
            'rate_per_minute' => $request->rate_per_minute,
            'discount_percentage' => $request->discount_percentage,
            'flat_rate_applied' => $request->flat_rate_applied ?? false,
            'subtotal' => $request->subtotal,
            'discount_amount' => $request->discount_amount,
            'tax_amount' => $request->tax_amount,
            'total' => $request->total,
            'entry_id' => $request->entry_id,
        ]);

        // Cargar relaciones
        $invoice->load([
            'entry.vehicle',
            'entry.branch',
            'invoiceTaxes.tax',
        ]);

        return $this->successResponse(
            $invoice,
            'Factura actualizada exitosamente',
            200
        );
    }

    /**
     * Eliminar factura
     *
     * @OA\Delete(
     *     path="/api/admin/invoices/{id}",
     *     tags={"Facturas"},
     *     summary="Eliminar factura",
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
        $invoice = Invoice::find($id);

        if (! $invoice) {
            return $this->errorResponse(
                'Factura no encontrada',
                ['invoice' => ['La factura no existe.']],
                404
            );
        }

        $invoice->delete();

        return $this->successResponse(
            null,
            'Factura eliminada exitosamente',
            200
        );
    }
}
