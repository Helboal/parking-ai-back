<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * Listar clientes
     *
     * @OA\Get(
     *     path="/api/admin/customers",
     *     tags={"Clientes"},
     *     summary="Listar clientes",
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
        $customers = Customer::with('documentType')->get();

        return $this->successResponse(
            $customers,
            'Listado de clientes',
            200
        );
    }

    /**
     * Crear cliente
     *
     * @OA\Post(
     *     path="/api/admin/customers",
     *     tags={"Clientes"},
     *     summary="Crear cliente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"document_number", "first_name", "last_name", "document_type_id"},
     *
     *             @OA\Property(property="document_number", type="string", maxLength=50, example="1234567890"),
     *             @OA\Property(property="first_name", type="string", maxLength=80, example="Juan"),
     *             @OA\Property(property="last_name", type="string", maxLength=80, example="Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="6012345678"),
     *             @OA\Property(property="mobile", type="string", maxLength=20, example="3001234567"),
     *             @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-15"),
     *             @OA\Property(property="photo_url", type="string", maxLength=255, example="https://example.com/photo.jpg"),
     *             @OA\Property(property="notes", type="string", example="Cliente frecuente"),
     *             @OA\Property(property="document_type_id", type="integer", example=1)
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
            'document_number' => 'required|string|max:50',
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'email' => 'nullable|email|max:100|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'photo_url' => 'nullable|string|max:255|url',
            'notes' => 'nullable|string',
            'document_type_id' => 'required|exists:document_types,id',
        ]);

        // Validación adicional: documento único por tipo
        $validator->after(function ($validator) use ($request) {
            if ($request->document_type_id && $request->document_number) {
                $exists = Customer::where('document_type_id', $request->document_type_id)
                    ->where('document_number', $request->document_number)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'document_number',
                        'Ya existe un cliente con este tipo y número de documento.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear cliente
        $customer = Customer::create([
            'document_number' => $request->document_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'photo_url' => $request->photo_url,
            'notes' => $request->notes,
            'document_type_id' => $request->document_type_id,
        ]);

        // Cargar relaciones
        $customer->load('documentType');

        return $this->successResponse(
            $customer,
            'Cliente creado exitosamente',
            201
        );
    }

    /**
     * Obtener cliente por ID
     *
     * @OA\Get(
     *     path="/api/admin/customers/{id}",
     *     tags={"Clientes"},
     *     summary="Obtener cliente",
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
        $customer = Customer::with('documentType')->find($id);

        if (! $customer) {
            return $this->errorResponse(
                'Cliente no encontrado',
                ['customer' => ['El cliente no existe.']],
                404
            );
        }

        return $this->successResponse(
            $customer,
            'Cliente encontrado',
            200
        );
    }

    /**
     * Actualizar cliente
     *
     * @OA\Put(
     *     path="/api/admin/customers/{id}",
     *     tags={"Clientes"},
     *     summary="Actualizar cliente",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"document_number", "first_name", "last_name", "document_type_id"},
     *
     *             @OA\Property(property="document_number", type="string", maxLength=50),
     *             @OA\Property(property="first_name", type="string", maxLength=80),
     *             @OA\Property(property="last_name", type="string", maxLength=80),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string", maxLength=20),
     *             @OA\Property(property="mobile", type="string", maxLength=20),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="birth_date", type="string", format="date"),
     *             @OA\Property(property="photo_url", type="string", maxLength=255),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="document_type_id", type="integer")
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
        $customer = Customer::find($id);

        if (! $customer) {
            return $this->errorResponse(
                'Cliente no encontrado',
                ['customer' => ['El cliente no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'document_number' => 'required|string|max:50',
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('customers')->ignore($customer->id),
            ],
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'photo_url' => 'nullable|string|max:255|url',
            'notes' => 'nullable|string',
            'document_type_id' => 'required|exists:document_types,id',
        ]);

        // Validación adicional: documento único por tipo (excluyendo el cliente actual)
        $validator->after(function ($validator) use ($request, $customer) {
            if ($request->document_type_id && $request->document_number) {
                $exists = Customer::where('document_type_id', $request->document_type_id)
                    ->where('document_number', $request->document_number)
                    ->where('id', '!=', $customer->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'document_number',
                        'Ya existe un cliente con este tipo y número de documento.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar cliente
        $customer->update([
            'document_number' => $request->document_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'photo_url' => $request->photo_url,
            'notes' => $request->notes,
            'document_type_id' => $request->document_type_id,
        ]);

        // Cargar relaciones
        $customer->load('documentType');

        return $this->successResponse(
            $customer,
            'Cliente actualizado exitosamente',
            200
        );
    }

    /**
     * Eliminar cliente
     *
     * @OA\Delete(
     *     path="/api/admin/customers/{id}",
     *     tags={"Clientes"},
     *     summary="Eliminar cliente",
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
        $customer = Customer::find($id);

        if (! $customer) {
            return $this->errorResponse(
                'Cliente no encontrado',
                ['customer' => ['El cliente no existe.']],
                404
            );
        }

        $customer->delete();

        return $this->successResponse(
            null,
            'Cliente eliminado exitosamente',
            200
        );
    }
}
