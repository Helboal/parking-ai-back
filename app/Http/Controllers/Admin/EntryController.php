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
     * Crear entrada con placa del vehículo
     *
     * @OA\Post(
     *     path="/api/admin/entries",
     *     tags={"Entradas"},
     *     summary="PASO 1: Registrar entrada por placa (sede auto-detectada)",
     *     description="PASO 1 del flujo de parqueadero. Registra la entrada usando SOLO la placa. SEDE: Se detecta automáticamente del usuario autenticado (sede primaria). VALIDACIÓN: Formato obligatorio CARRO (3L+3N ej.KOR074) o MOTO (3L+2N+1L ej.ART46G). FLUJO: Si vehículo EXISTE: guarda vehicle_id, detecta suscripción activa automáticamente. Si vehículo NO EXISTE: guarda solo placa (vehicle_id=NULL), detecta tipo por patrón.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"license_plate"},
     *
     *             @OA\Property(property="license_plate", type="string", example="KOR074", description="Placa del vehículo (se normaliza automáticamente a mayúsculas). No es necesario que el vehículo esté registrado. El tipo se detecta por patrón: CARRO=3letras+3números, MOTO=3letras+2números+1letra"),
     *             @OA\Property(property="notes", type="string", example="Entrada registrada por cámara", description="Notas adicionales sobre la entrada (opcional)")
         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Entrada registrada exitosamente con información contextual completa",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Entrada registrada exitosamente para vehículo registrado"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="entry", type="object", description="Datos de la entrada registrada con todas sus relaciones"),
     *                 @OA\Property(property="vehicle_registered", type="boolean", example=true, description="Indica si el vehículo está registrado en el sistema (true) o es entrada con placa únicamente (false)"),
     *                 @OA\Property(property="vehicle_info", type="object", description="Información del vehículo. La estructura varía según si está registrado o no",
     *                     @OA\Property(property="license_plate", type="string", example="KOR074", description="Placa del vehículo (siempre presente)"),
     *                     @OA\Property(property="brand", type="string", example="Toyota", description="Marca del vehículo (solo si vehicle_registered=true)"),
     *                     @OA\Property(property="model", type="string", example="Corolla", description="Modelo del vehículo (solo si vehicle_registered=true)"),
     *                     @OA\Property(property="color", type="string", example="Blanco", description="Color del vehículo (solo si vehicle_registered=true)"),
     *                     @OA\Property(property="vehicle_type", type="string", example="Automóvil", description="Tipo de vehículo (solo si vehicle_registered=true)"),
     *                     @OA\Property(property="registered", type="boolean", example=false, description="false cuando vehicle_registered=false (solo incluye license_plate)")
     *                 ),
     *                 @OA\Property(property="customer_info", type="object", nullable=true, description="Información del cliente propietario (solo si vehicle_registered=true y tiene cliente asignado, NULL en otros casos)",
     *                     @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                     @OA\Property(property="document", type="string", example="CC 1234567890"),
     *                     @OA\Property(property="phone", type="string", example="3001234567"),
     *                     @OA\Property(property="email", type="string", example="juan@example.com")
     *                 ),
     *                 @OA\Property(property="subscription_info", type="object", nullable=true, description="Información de suscripción activa detectada automáticamente (solo si vehicle_registered=true y el cliente tiene suscripción activa, NULL en otros casos)",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="Mensual"),
     *                     @OA\Property(property="start_date", type="string", example="2025-01-01"),
     *                     @OA\Property(property="end_date", type="string", example="2025-01-31"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="capacity_info", type="object", description="Información de capacidad del parqueadero actualizada (siempre presente)",
     *                     @OA\Property(property="total_spaces", type="integer", example=50),
     *                     @OA\Property(property="occupied_spaces", type="integer", example=23, description="Incluye el vehículo que acaba de entrar"),
     *                     @OA\Property(property="available_spaces", type="integer", example=27)
     *                 )
     *             ),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Errores de validación: formato de placa inválido, vehículo ya dentro del parqueadero, sin capacidad disponible, usuario sin sede primaria asignada"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación - Solo requiere placa con formato válido
        $validator = Validator::make($request->all(), [
            'license_plate' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z]{3}(\d{3}|\d{2}[A-Z])$/i', // CARRO: 3L+3N o MOTO: 3L+2N+1L
            ],
            'notes' => 'nullable|string',
        ], [
            'license_plate.required' => 'La placa del vehículo es requerida.',
            'license_plate.string' => 'La placa debe ser texto.',
            'license_plate.max' => 'La placa no puede exceder 20 caracteres.',
            'license_plate.regex' => 'La placa debe tener un formato válido: 3 letras + 3 números (CARRO) o 3 letras + 2 números + 1 letra (MOTO). Ejemplo: KOR074 o ART46G',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Obtener la sede del usuario autenticado (sede primaria)
        $user = auth()->user();
        $primaryBranch = $user->primaryBranch()->with('branch')->first();

        if (! $primaryBranch || ! $primaryBranch->branch) {
            return $this->errorResponse(
                'Usuario sin sede asignada',
                ['user' => ['El usuario no tiene una sede primaria asignada. Contacte al administrador.']],
                422
            );
        }

        $branchId = $primaryBranch->branch_id;

        // Normalizar la placa (mayúsculas, sin espacios)
        $licensePlate = strtoupper(trim($request->license_plate));

        // LÓGICA DE NEGOCIO: Buscar el vehículo por placa (puede existir o no)
        $vehicle = \App\Models\Vehicle::with(['vehicleType', 'customer.documentType'])
            ->where('license_plate', $licensePlate)
            ->first();

        // Variables para la entrada
        $vehicleId = null;
        $vehicleTypeId = null;
        $subscription = null;
        $entryTypeCode = 'REGULAR';
        $saveLicensePlate = null;

        // CASO 1: Vehículo EXISTE en el sistema
        if ($vehicle) {
            $vehicleId = $vehicle->id;
            $vehicleTypeId = $vehicle->vehicle_type_id;
            $saveLicensePlate = null; // No guardamos la placa si ya tenemos vehicle_id

            // Verificar que el vehículo no esté actualmente en el parqueadero
            $activeEntry = Entry::where('vehicle_id', $vehicle->id)
                ->where('status', 'active')
                ->first();

            if ($activeEntry) {
                return $this->errorResponse(
                    'El vehículo ya se encuentra en el parqueadero',
                    ['vehicle' => ['El vehículo con placa '.$licensePlate.' ya tiene una entrada activa desde '.$activeEntry->entry_datetime->format('Y-m-d H:i:s').'. Debe registrar la salida primero.']],
                    422
                );
            }

            // Buscar suscripción activa del cliente (automático)
            if ($vehicle->customer) {
                $subscription = \App\Models\Subscription::where('customer_id', $vehicle->customer_id)
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->first();

                if ($subscription) {
                    $entryTypeCode = 'SUBSCRIPTION';
                }
            }
        }
        // CASO 2: Vehículo NO EXISTE en el sistema
        else {
            $vehicleId = null;
            $saveLicensePlate = $licensePlate; // Guardamos la placa porque no hay vehicle_id

            // Verificar que no haya una entrada activa con esta placa
            $activeEntry = Entry::where('license_plate', $licensePlate)
                ->where('status', 'active')
                ->first();

            if ($activeEntry) {
                return $this->errorResponse(
                    'El vehículo ya se encuentra en el parqueadero',
                    ['vehicle' => ['El vehículo con placa '.$licensePlate.' ya tiene una entrada activa desde '.$activeEntry->entry_datetime->format('Y-m-d H:i:s').'. Debe registrar la salida primero.']],
                    422
                );
            }

            // Detectar tipo de vehículo por patrón de placa
            // CARRO: 3 letras + 3 números (ejemplo: KOR074)
            // MOTO: 3 letras + 2 números + 1 letra (ejemplo: ART46G)
            $vehicleTypeCode = 'CAR'; // Default

            if (preg_match('/^[A-Z]{3}\d{3}$/', $licensePlate)) {
                // Es un CARRO
                $vehicleTypeCode = 'CAR';
            } elseif (preg_match('/^[A-Z]{3}\d{2}[A-Z]$/', $licensePlate)) {
                // Es una MOTO
                $vehicleTypeCode = 'MOTORCYCLE';
            }

            $detectedVehicleType = \App\Models\VehicleType::where('code', $vehicleTypeCode)->first();
            if (! $detectedVehicleType) {
                // Fallback al primer tipo disponible
                $detectedVehicleType = \App\Models\VehicleType::first();
            }
            $vehicleTypeId = $detectedVehicleType ? $detectedVehicleType->id : null;
        }

        // VALIDAR CAPACIDAD (para ambos casos)
        if ($vehicleTypeId) {
            $capacity = \App\Models\BranchParkingCapacity::where('branch_id', $branchId)
                ->where('vehicle_type_id', $vehicleTypeId)
                ->first();

            if (! $capacity) {
                return $this->errorResponse(
                    'No hay configuración de capacidad para este tipo de vehículo en esta sede',
                    ['capacity' => ['La sede no tiene configurada capacidad para este tipo de vehículo.']],
                    422
                );
            }

            if ($capacity->occupied_spaces >= $capacity->total_spaces) {
                return $this->errorResponse(
                    'No hay cupos disponibles',
                    ['capacity' => ['No hay espacios disponibles en esta sede. Espacios ocupados: '.$capacity->occupied_spaces.'/'.$capacity->total_spaces]],
                    422
                );
            }
        } else {
            return $this->errorResponse(
                'Error de configuración',
                ['system' => ['No se pudo determinar el tipo de vehículo. Contacte al administrador.']],
                500
            );
        }

        // Obtener el entry_type_id según si tiene suscripción o no
        $entryType = \App\Models\EntryType::where('code', $entryTypeCode)->first();
        if (! $entryType) {
            $entryType = \App\Models\EntryType::first(); // Fallback
        }

        // Crear entrada
        $entry = Entry::create([
            'entry_datetime' => now(),
            'exit_datetime' => null,
            'total_minutes' => null,
            'status' => 'active',
            'entry_user_id' => auth()->id(),
            'exit_user_id' => null,
            'branch_id' => $branchId, // Usar sede del usuario autenticado
            'vehicle_id' => $vehicleId, // Puede ser null si el vehículo no existe
            'license_plate' => $saveLicensePlate, // Solo se guarda si vehicle_id es null
            'entry_type_id' => $entryType->id,
            'subscription_id' => $subscription ? $subscription->id : null,
            'notes' => $request->notes,
        ]);

        // LÓGICA DE NEGOCIO: Incrementar cupos ocupados
        $capacity->increment('occupied_spaces');

        // Cargar relaciones si el vehículo existe
        if ($entry->vehicle_id) {
            $entry->load([
                'entryUser',
                'branch',
                'vehicle.vehicleType',
                'vehicle.customer.documentType',
                'entryType',
                'subscription.subscriptionType',
            ]);
        } else {
            $entry->load([
                'entryUser',
                'branch',
                'entryType',
            ]);
        }

        // Preparar respuesta con contexto completo
        $responseData = [
            'entry' => $entry,
            'vehicle_registered' => $entry->vehicle_id ? true : false,
        ];

        // Si el vehículo está registrado, incluir toda la información
        if ($entry->vehicle_id) {
            $responseData['vehicle_info'] = [
                'license_plate' => $entry->vehicle->license_plate,
                'brand' => $entry->vehicle->brand,
                'model' => $entry->vehicle->model,
                'color' => $entry->vehicle->color,
                'vehicle_type' => $entry->vehicle->vehicleType->name,
            ];
            $responseData['customer_info'] = $entry->vehicle->customer ? [
                'name' => $entry->vehicle->customer->first_name.' '.$entry->vehicle->customer->last_name,
                'document' => $entry->vehicle->customer->documentType->code.' '.$entry->vehicle->customer->document_number,
                'phone' => $entry->vehicle->customer->phone,
                'email' => $entry->vehicle->customer->email,
            ] : null;
            $responseData['subscription_info'] = $entry->subscription ? [
                'id' => $entry->subscription->id,
                'type' => $entry->subscription->subscriptionType->name,
                'start_date' => $entry->subscription->start_date,
                'end_date' => $entry->subscription->end_date,
                'is_active' => $entry->subscription->is_active,
            ] : null;
        } else {
            // Vehículo no registrado, solo devolver la placa
            $responseData['vehicle_info'] = [
                'license_plate' => $entry->license_plate,
                'registered' => false,
            ];
            $responseData['customer_info'] = null;
            $responseData['subscription_info'] = null;
        }

        $responseData['capacity_info'] = [
            'total_spaces' => $capacity->total_spaces,
            'occupied_spaces' => $capacity->occupied_spaces,
            'available_spaces' => $capacity->total_spaces - $capacity->occupied_spaces,
        ];

        $message = $entry->vehicle_id
            ? 'Entrada registrada exitosamente para vehículo registrado'
            : 'Entrada registrada exitosamente para vehículo no registrado';

        return $this->successResponse(
            $responseData,
            $message,
            200
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
     *     summary="[ADMINISTRATIVO] Actualizar entrada manualmente",
     *     description="SOLO PARA CORRECCIONES ADMINISTRATIVAS. NO usar en el flujo normal del parqueadero. El flujo correcto es: PASO 1 (POST /entries), PASO 2 (POST /entries/{license_plate}/invoice), PASO 3 (POST /entries/{license_plate}/release). Use este endpoint únicamente para corregir datos erróneos o realizar ajustes manuales por problemas del sistema.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"entry_datetime", "status", "branch_id", "entry_type_id"},
     *
     *             @OA\Property(property="entry_datetime", type="string", format="date-time", description="Fecha y hora de entrada"),
     *             @OA\Property(property="exit_datetime", type="string", format="date-time", nullable=true, description="Fecha y hora de salida"),
     *             @OA\Property(property="total_minutes", type="integer", nullable=true, description="Total de minutos transcurridos"),
     *             @OA\Property(property="status", type="string", enum={"active", "completed", "cancelled"}, description="Estado de la entrada"),
     *             @OA\Property(property="entry_user_id", type="integer", nullable=true, description="ID del usuario que registró la entrada"),
     *             @OA\Property(property="exit_user_id", type="integer", nullable=true, description="ID del usuario que registró la salida"),
     *             @OA\Property(property="branch_id", type="integer", description="ID de la sede"),
     *             @OA\Property(property="vehicle_id", type="integer", nullable=true, description="ID del vehículo registrado (NULL si no está registrado)"),
     *             @OA\Property(property="license_plate", type="string", nullable=true, example="KOR074", description="Placa del vehículo (requerida si vehicle_id es NULL)"),
     *             @OA\Property(property="entry_type_id", type="integer", description="ID del tipo de entrada"),
     *             @OA\Property(property="subscription_id", type="integer", nullable=true, description="ID de la suscripción (si aplica)"),
     *             @OA\Property(property="notes", type="string", nullable=true, description="Notas adicionales")
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
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'license_plate' => 'nullable|string|max:20|regex:/^[A-Z]{3}(\d{3}|\d{2}[A-Z])$/i',
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
            'vehicle_id.exists' => 'El vehículo no existe.',
            'license_plate.string' => 'La placa debe ser texto.',
            'license_plate.max' => 'La placa no puede exceder 20 caracteres.',
            'license_plate.regex' => 'La placa debe tener formato válido: 3 letras + 3 números (CARRO) o 3 letras + 2 números + 1 letra (MOTO).',
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
            'license_plate' => $request->license_plate ? strtoupper(trim($request->license_plate)) : null,
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
     *     summary="[ADMINISTRATIVO] Eliminar entrada",
     *     description="SOLO PARA CORRECCIONES ADMINISTRATIVAS. NO usar en el flujo normal del parqueadero. Este endpoint elimina permanentemente un registro de entrada. Use solo para corregir entradas duplicadas o registros de prueba. ADVERTENCIA: Esto NO libera automáticamente el espacio de parqueadero.",
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
     * Generar factura y procesar pago para un vehículo (PASO 2 del flujo)
     *
     * @OA\Post(
     *     path="/api/admin/entries/{license_plate}/invoice",
     *     tags={"Entradas"},
     *     summary="PASO 2: Generar factura y procesar pago",
     *     description="PASO 2 del flujo de parqueadero. Busca vehículo por placa (debe estar adentro con status='active'), calcula tiempo transcurrido, aplica tarifas/descuentos/impuestos, genera factura y procesa pago EN UNA SOLA OPERACIÓN. CON SUSCRIPCIÓN: Genera factura con total=$0 y relaciona payment existente de la suscripción. SIN SUSCRIPCIÓN: Calcula tarifa normal por minuto. IMPORTANTE: La entrada permanece activa (status='active') después del pago, solo cambia a 'completed' en PASO 3 (salida física).",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="license_plate", in="path", required=true, @OA\Schema(type="string"), example="KOR074", description="Placa del vehículo"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"payment_method_id"},
     *
     *             @OA\Property(property="payment_method_id", type="integer", example=1, description="ID del método de pago (efectivo, tarjeta, etc.)"),
     *             @OA\Property(property="notes", type="string", example="Pago recibido en efectivo")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Factura generada y pago procesado exitosamente"),
     *     @OA\Response(response=404, description="Vehículo no encontrado o no está en el parqueadero"),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  string  $licensePlate
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateInvoiceAndPayment(Request $request, $licensePlate)
    {
        // Normalizar la placa
        $licensePlate = strtoupper(trim($licensePlate));

        // Validación
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|exists:payment_methods,id',
            'notes' => 'nullable|string',
        ], [
            'payment_method_id.required' => 'El método de pago es requerido.',
            'payment_method_id.exists' => 'El método de pago no existe.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Buscar entrada activa por placa (puede ser vehículo registrado o no registrado)
        $entry = Entry::with(['vehicle.vehicleType', 'vehicle.customer', 'branch', 'subscription', 'invoice'])
            ->where('status', 'active')
            ->where(function ($query) use ($licensePlate) {
                $query->where('license_plate', $licensePlate)
                    ->orWhereHas('vehicle', function ($q) use ($licensePlate) {
                        $q->where('license_plate', $licensePlate);
                    });
            })
            ->first();

        if (! $entry) {
            return $this->errorResponse(
                'Vehículo no encontrado en el parqueadero',
                ['entry' => ['No hay ninguna entrada activa para el vehículo con placa '.$licensePlate]],
                404
            );
        }

        // Validar que no tenga factura ya generada
        if ($entry->invoice) {
            return $this->errorResponse(
                'El vehículo ya tiene una factura generada',
                ['invoice' => ['Ya se generó una factura para esta entrada. El vehículo puede proceder a la salida.']],
                422
            );
        }

        // Calcular tiempo transcurrido
        $exitDatetime = now();
        $totalMinutes = $entry->entry_datetime->diffInMinutes($exitDatetime);

        // Determinar el vehicle_type_id
        $vehicleTypeId = null;
        if ($entry->vehicle_id && $entry->vehicle) {
            $vehicleTypeId = $entry->vehicle->vehicle_type_id;
        } else {
            // Detectar por patrón de placa
            $plateToCheck = $entry->license_plate;
            $vehicleTypeCode = 'CAR';

            if ($plateToCheck) {
                if (preg_match('/^[A-Z]{3}\d{3}$/', $plateToCheck)) {
                    $vehicleTypeCode = 'CAR';
                } elseif (preg_match('/^[A-Z]{3}\d{2}[A-Z]$/', $plateToCheck)) {
                    $vehicleTypeCode = 'MOTORCYCLE';
                }
            }

            $detectedVehicleType = \App\Models\VehicleType::where('code', $vehicleTypeCode)->first();
            $vehicleTypeId = $detectedVehicleType ? $detectedVehicleType->id : null;
        }

        if (! $vehicleTypeId) {
            return $this->errorResponse(
                'Error al determinar tipo de vehículo',
                ['vehicle_type' => ['No se pudo determinar el tipo de vehículo para calcular la tarifa.']],
                500
            );
        }

        // CASO 1: Entrada por SUSCRIPCIÓN - Generar factura con total=0
        if ($entry->subscription_id && $entry->subscription) {
            // Buscar el payment de la suscripción
            $subscriptionPayment = \App\Models\Payment::where('subscription_id', $entry->subscription_id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Crear factura con total=0
            $invoice = \App\Models\Invoice::create([
                'rate_per_minute' => 0,
                'discount_percentage' => 0,
                'flat_rate_applied' => false,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'entry_id' => $entry->id,
            ]);

            // Si existe un payment de suscripción, crear un payment relacionado a esta factura
            $payment = null;
            if ($subscriptionPayment) {
                $payment = \App\Models\Payment::create([
                    'amount' => 0,
                    'payment_datetime' => now(),
                    'payment_method_id' => $request->payment_method_id,
                    'notes' => $request->notes ?? 'Pago cubierto por suscripción',
                    'user_id' => auth()->id(),
                    'subscription_id' => $entry->subscription_id,
                    'invoice_id' => $invoice->id,
                ]);
            }

            $entry->load(['invoice', 'subscription.subscriptionType']);

            return $this->successResponse(
                [
                    'entry' => $entry,
                    'invoice' => $invoice,
                    'payment' => $payment,
                    'subscription_applied' => true,
                    'message' => 'Entrada cubierta por suscripción. Factura generada con total $0. El vehículo puede proceder a la salida.',
                ],
                'Factura generada exitosamente. Entrada por suscripción.',
                200
            );
        }

        // CASO 2: Entrada REGULAR - Calcular factura normal
        // 1. Obtener tarifa por minuto
        $branchRate = \App\Models\BranchRate::where('branch_id', $entry->branch_id)
            ->where('vehicle_type_id', $vehicleTypeId)
            ->where('is_active', true)
            ->first();

        if (! $branchRate) {
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
            ->where('vehicle_type_id', $vehicleTypeId)
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
            ->where('vehicle_type_id', $vehicleTypeId)
            ->where('is_active', true)
            ->where('minutes', '<=', $totalMinutes)
            ->orderBy('discount_percentage', 'desc')
            ->first();

        if ($branchDiscount) {
            $discountPercentage = $branchDiscount->discount_percentage;
        }

        $discountAmount = ($subtotal * $discountPercentage) / 100;

        // 5. Calcular base imponible
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

        // 10. Procesar pago
        $payment = \App\Models\Payment::create([
            'amount' => round($total, 2),
            'payment_datetime' => now(),
            'payment_method_id' => $request->payment_method_id,
            'notes' => $request->notes,
            'user_id' => auth()->id(),
            'subscription_id' => null,
            'invoice_id' => $invoice->id,
        ]);

        // Cargar relaciones
        $entry->load([
            'invoice.invoiceTaxes.tax',
            'vehicle.customer',
            'branch',
        ]);

        return $this->successResponse(
            [
                'entry' => $entry,
                'invoice' => $invoice->load('invoiceTaxes.tax'),
                'payment' => $payment,
                'total_minutes' => $totalMinutes,
                'message' => 'Factura generada y pago procesado exitosamente. El vehículo puede proceder a la salida.',
            ],
            'Factura y pago procesados exitosamente',
            200
        );
    }

    /**
     * Registrar salida física del vehículo (PASO 3 del flujo)
     *
     * @OA\Post(
     *     path="/api/admin/entries/{license_plate}/release",
     *     tags={"Entradas"},
     *     summary="PASO 3: Registrar salida física del vehículo",
     *     description="PASO 3 del flujo de parqueadero (FINAL). Busca vehículo por placa y VALIDA que haya pagado (debe tener factura generada en PASO 2). Si no pagó: error 422 'Debe pagar primero'. Si pagó: registra salida física (exit_datetime=now()), cambia status a 'completed', libera espacio de parqueadero. Funciona tanto para vehículos registrados como no registrados.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="license_plate", in="path", required=true, @OA\Schema(type="string"), example="KOR074", description="Placa del vehículo"),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="notes", type="string", example="Salida sin novedad")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Salida registrada exitosamente"),
     *     @OA\Response(response=404, description="Vehículo no encontrado en el parqueadero"),
     *     @OA\Response(response=422, description="El vehículo no ha pagado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param  string  $licensePlate
     * @return \Illuminate\Http\JsonResponse
     */
    public function releaseVehicle(Request $request, $licensePlate)
    {
        // Normalizar la placa
        $licensePlate = strtoupper(trim($licensePlate));

        // Buscar entrada activa por placa
        $entry = Entry::with(['vehicle.vehicleType', 'vehicle.customer', 'branch', 'subscription', 'invoice'])
            ->where('status', 'active')
            ->where(function ($query) use ($licensePlate) {
                $query->where('license_plate', $licensePlate)
                    ->orWhereHas('vehicle', function ($q) use ($licensePlate) {
                        $q->where('license_plate', $licensePlate);
                    });
            })
            ->first();

        if (! $entry) {
            return $this->errorResponse(
                'Vehículo no encontrado en el parqueadero',
                ['entry' => ['No hay ninguna entrada activa para el vehículo con placa '.$licensePlate]],
                404
            );
        }

        // VALIDAR QUE HAYA PAGADO - Debe tener una factura generada
        if (! $entry->invoice) {
            return $this->errorResponse(
                'Debe pagar primero',
                ['payment' => ['El vehículo no ha generado la factura ni realizado el pago. Debe dirigirse a la caja antes de salir.']],
                422
            );
        }

        // Actualizar entrada - Registrar salida física
        $exitDatetime = now();
        $totalMinutes = $entry->entry_datetime->diffInMinutes($exitDatetime);

        $entry->update([
            'exit_datetime' => $exitDatetime,
            'total_minutes' => $totalMinutes,
            'status' => 'completed',
            'exit_user_id' => auth()->id(),
            'notes' => $request->notes ?? $entry->notes,
        ]);

        // Determinar vehicle_type_id para liberar el espacio correcto
        $vehicleTypeId = null;
        if ($entry->vehicle_id && $entry->vehicle) {
            $vehicleTypeId = $entry->vehicle->vehicle_type_id;
        } else {
            // Detectar por patrón de placa
            $plateToCheck = $entry->license_plate;
            $vehicleTypeCode = 'CAR';

            if ($plateToCheck) {
                if (preg_match('/^[A-Z]{3}\d{3}$/', $plateToCheck)) {
                    $vehicleTypeCode = 'CAR';
                } elseif (preg_match('/^[A-Z]{3}\d{2}[A-Z]$/', $plateToCheck)) {
                    $vehicleTypeCode = 'MOTORCYCLE';
                }
            }

            $detectedVehicleType = \App\Models\VehicleType::where('code', $vehicleTypeCode)->first();
            $vehicleTypeId = $detectedVehicleType ? $detectedVehicleType->id : null;
        }

        // Liberar cupo
        if ($vehicleTypeId) {
            $capacity = \App\Models\BranchParkingCapacity::where('branch_id', $entry->branch_id)
                ->where('vehicle_type_id', $vehicleTypeId)
                ->first();

            if ($capacity && $capacity->occupied_spaces > 0) {
                $capacity->decrement('occupied_spaces');
            }
        }

        // Cargar relaciones
        $entry->load([
            'invoice.invoiceTaxes.tax',
            'vehicle.customer',
            'branch',
            'exitUser',
        ]);

        return $this->successResponse(
            [
                'entry' => $entry,
                'invoice' => $entry->invoice,
                'message' => 'Salida registrada exitosamente. Espacio liberado.',
            ],
            'Salida registrada exitosamente',
            200
        );
    }
}
