<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Traits\ApiResponse;

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
     *     description="Obtiene todas las facturas generadas automáticamente",
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
            'entry.vehicle.customer',
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
     * Obtener facturas pendientes de pago
     *
     * @OA\Get(
     *     path="/api/admin/invoices/pending",
     *     tags={"Facturas"},
     *     summary="Listar facturas pendientes de pago",
     *     description="Obtiene todas las facturas que no han sido pagadas completamente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Listado exitoso"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pending()
    {
        // Obtener todas las facturas con sus pagos
        $invoices = Invoice::with([
            'entry.vehicle.customer',
            'entry.branch',
            'invoiceTaxes.tax',
        ])->get();

        $pendingInvoices = [];

        foreach ($invoices as $invoice) {
            // Calcular total pagado
            $totalPaid = \App\Models\Payment::where('invoice_id', $invoice->id)
                ->where('status', 'completed')
                ->sum('amount');

            // Si no está completamente pagada, agregarla
            if ($totalPaid < $invoice->total) {
                $invoiceArray = $invoice->toArray();
                $invoiceArray['total_paid'] = $totalPaid;
                $invoiceArray['balance_due'] = $invoice->total - $totalPaid;
                $pendingInvoices[] = $invoiceArray;
            }
        }

        return $this->successResponse(
            $pendingInvoices,
            count($pendingInvoices) . ' facturas pendientes de pago',
            200
        );
    }

    /**
     * Obtener factura por ID
     *
     * @OA\Get(
     *     path="/api/admin/invoices/{id}",
     *     tags={"Facturas"},
     *     summary="Obtener factura",
     *     description="Obtiene los detalles de una factura específica",
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
            'entry.vehicle.customer',
            'entry.branch',
            'invoiceTaxes.tax',
        ])->find($id);

        if (!$invoice) {
            return $this->errorResponse(
                'Factura no encontrada',
                ['invoice' => ['La factura no existe.']],
                404
            );
        }

        // Calcular total pagado y balance pendiente
        $totalPaid = \App\Models\Payment::where('invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->sum('amount');

        $invoiceData = $invoice->toArray();
        $invoiceData['total_paid'] = $totalPaid;
        $invoiceData['balance_due'] = $invoice->total - $totalPaid;
        $invoiceData['is_paid'] = $totalPaid >= $invoice->total;

        return $this->successResponse(
            $invoiceData,
            'Factura encontrada',
            200
        );
    }
}
