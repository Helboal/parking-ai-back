<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $taxes = Tax::orderBy('name')->get();

        return $this->successResponse($taxes, 'Taxes retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:taxes,code',
            'percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $tax = Tax::create($validator->validated());

        return $this->successResponse($tax, 'Tax created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $tax = Tax::find($id);

        if (! $tax) {
            return $this->errorResponse('Tax not found', ['id' => ['The specified tax does not exist.']], 404);
        }

        return $this->successResponse($tax, 'Tax retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tax = Tax::find($id);

        if (! $tax) {
            return $this->errorResponse('Tax not found', ['id' => ['The specified tax does not exist.']], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20|unique:taxes,code,'.$id,
            'percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $tax->update($validator->validated());

        return $this->successResponse($tax, 'Tax updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $tax = Tax::find($id);

        if (! $tax) {
            return $this->errorResponse('Tax not found', ['id' => ['The specified tax does not exist.']], 404);
        }

        $tax->delete();

        return $this->successResponse(null, 'Tax deleted successfully');
    }
}
