<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of branches.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $branches = Branch::with(['user.documentType', 'user.roles'])->get();

        // Formatear respuesta
        $branchesData = $branches->map(function ($branch) {
            $userData = null;
            if ($branch->user) {
                $role = $branch->user->roles()->first();
                $roleData = null;
                if ($role) {
                    $roleData = [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                        'permissions' => $role->permissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'guard_name' => $permission->guard_name,
                                'created_at' => $permission->created_at,
                                'updated_at' => $permission->updated_at,
                            ];
                        })->toArray(),
                    ];
                }

                $userData = [
                    'id' => $branch->user->id,
                    'name' => $branch->user->name,
                    'last_name' => $branch->user->last_name,
                    'email' => $branch->user->email,
                    'document_number' => $branch->user->document_number,
                    'phone' => $branch->user->phone,
                    'is_active' => $branch->user->is_active,
                    'document_type' => $branch->user->documentType,
                    'role' => $roleData,
                ];
            }

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'total_spaces' => $branch->total_spaces,
                'available_spaces' => $branch->available_spaces,
                'is_active' => $branch->is_active,
                'created_at' => $branch->created_at,
                'updated_at' => $branch->updated_at,
                'user' => $userData,
            ];
        });

        return $this->successResponse(
            $branchesData,
            'Listado de sedes',
            200
        );
    }

    /**
     * Store a newly created branch.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:branches,name',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'total_spaces' => 'required|integer|min:1',
            'available_spaces' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // Validación adicional: available_spaces <= total_spaces
        $validator->after(function ($validator) use ($request) {
            if ($request->available_spaces > $request->total_spaces) {
                $validator->errors()->add(
                    'available_spaces',
                    'Los espacios disponibles no pueden ser mayores que el total de espacios.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Crear sede
        $branch = Branch::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'total_spaces' => $request->total_spaces,
            'available_spaces' => $request->available_spaces,
            'is_active' => $request->is_active ?? true,
            'user_id' => $request->user_id,
        ]);

        // Cargar relaciones
        $branch->load(['user.documentType', 'user.roles']);

        // Formatear user con role
        $userData = null;
        if ($branch->user) {
            $role = $branch->user->roles()->first();
            $roleData = null;
            if ($role) {
                $roleData = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name,
                            'created_at' => $permission->created_at,
                            'updated_at' => $permission->updated_at,
                        ];
                    })->toArray(),
                ];
            }

            $userData = [
                'id' => $branch->user->id,
                'name' => $branch->user->name,
                'last_name' => $branch->user->last_name,
                'email' => $branch->user->email,
                'document_number' => $branch->user->document_number,
                'phone' => $branch->user->phone,
                'is_active' => $branch->user->is_active,
                'document_type' => $branch->user->documentType,
                'role' => $roleData,
            ];
        }

        return $this->successResponse(
            [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'total_spaces' => $branch->total_spaces,
                'available_spaces' => $branch->available_spaces,
                'is_active' => $branch->is_active,
                'created_at' => $branch->created_at,
                'updated_at' => $branch->updated_at,
                'user' => $userData,
            ],
            'Sede creada exitosamente',
            201
        );
    }

    /**
     * Display the specified branch.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $branch = Branch::with(['user.documentType', 'user.roles'])->find($id);

        if (! $branch) {
            return $this->errorResponse(
                'Sede no encontrada',
                ['branch' => ['La sede no existe.']],
                404
            );
        }

        // Formatear user con role
        $userData = null;
        if ($branch->user) {
            $role = $branch->user->roles()->first();
            $roleData = null;
            if ($role) {
                $roleData = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name,
                            'created_at' => $permission->created_at,
                            'updated_at' => $permission->updated_at,
                        ];
                    })->toArray(),
                ];
            }

            $userData = [
                'id' => $branch->user->id,
                'name' => $branch->user->name,
                'last_name' => $branch->user->last_name,
                'email' => $branch->user->email,
                'document_number' => $branch->user->document_number,
                'phone' => $branch->user->phone,
                'is_active' => $branch->user->is_active,
                'document_type' => $branch->user->documentType,
                'role' => $roleData,
            ];
        }

        return $this->successResponse(
            [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'total_spaces' => $branch->total_spaces,
                'available_spaces' => $branch->available_spaces,
                'is_active' => $branch->is_active,
                'created_at' => $branch->created_at,
                'updated_at' => $branch->updated_at,
                'user' => $userData,
            ],
            'Sede encontrada',
            200
        );
    }

    /**
     * Update the specified branch.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            return $this->errorResponse(
                'Sede no encontrada',
                ['branch' => ['La sede no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('branches')->ignore($branch->id),
            ],
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'total_spaces' => 'required|integer|min:1',
            'available_spaces' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // Validación adicional: available_spaces <= total_spaces
        $validator->after(function ($validator) use ($request) {
            if ($request->available_spaces > $request->total_spaces) {
                $validator->errors()->add(
                    'available_spaces',
                    'Los espacios disponibles no pueden ser mayores que el total de espacios.'
                );
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Actualizar sede
        $branch->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'total_spaces' => $request->total_spaces,
            'available_spaces' => $request->available_spaces,
            'is_active' => $request->is_active ?? $branch->is_active,
            'user_id' => $request->user_id,
        ]);

        // Cargar relaciones
        $branch->load(['user.documentType', 'user.roles']);

        // Formatear user con role
        $userData = null;
        if ($branch->user) {
            $role = $branch->user->roles()->first();
            $roleData = null;
            if ($role) {
                $roleData = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name,
                            'created_at' => $permission->created_at,
                            'updated_at' => $permission->updated_at,
                        ];
                    })->toArray(),
                ];
            }

            $userData = [
                'id' => $branch->user->id,
                'name' => $branch->user->name,
                'last_name' => $branch->user->last_name,
                'email' => $branch->user->email,
                'document_number' => $branch->user->document_number,
                'phone' => $branch->user->phone,
                'is_active' => $branch->user->is_active,
                'document_type' => $branch->user->documentType,
                'role' => $roleData,
            ];
        }

        return $this->successResponse(
            [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'total_spaces' => $branch->total_spaces,
                'available_spaces' => $branch->available_spaces,
                'is_active' => $branch->is_active,
                'created_at' => $branch->created_at,
                'updated_at' => $branch->updated_at,
                'user' => $userData,
            ],
            'Sede actualizada exitosamente',
            200
        );
    }

    /**
     * Remove the specified branch (soft delete).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            return $this->errorResponse(
                'Sede no encontrada',
                ['branch' => ['La sede no existe.']],
                404
            );
        }

        $branch->delete();

        return $this->successResponse(
            null,
            'Sede eliminada exitosamente',
            200
        );
    }
}
