<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = User::with(['documentType', 'roles'])->get();

        // Formatear respuesta con rol y permisos
        $usersData = $users->map(function ($user) {
            $role = $user->roles()->first();
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

            return [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'document_number' => $user->document_number,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'document_type' => $user->documentType,
                'role' => $roleData,
            ];
        });

        return $this->successResponse(
            $usersData,
            'Listado de usuarios',
            200
        );
    }

    /**
     * Store a newly created user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'document_number' => 'required|string|max:50',
            'document_type_id' => 'required|exists:document_types,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Validación adicional: documento único por tipo
        $validator->after(function ($validator) use ($request) {
            if ($request->document_type_id && $request->document_number) {
                $exists = User::where('document_type_id', $request->document_type_id)
                    ->where('document_number', $request->document_number)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'document_number',
                        'Ya existe un usuario con este tipo y número de documento.'
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

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'document_number' => $request->document_number,
            'document_type_id' => $request->document_type_id,
            'phone' => $request->phone,
            'is_active' => $request->is_active ?? true,
        ]);

        // Asignar rol
        $role = Role::find($request->role_id);
        if ($role) {
            $user->assignRole($role);
        }

        // Cargar relaciones
        $user->load(['documentType', 'roles']);

        // Formatear rol con permisos
        $roleData = null;
        $userRole = $user->roles()->first();
        if ($userRole) {
            $roleData = [
                'id' => $userRole->id,
                'name' => $userRole->name,
                'guard_name' => $userRole->guard_name,
                'created_at' => $userRole->created_at,
                'updated_at' => $userRole->updated_at,
                'permissions' => $userRole->permissions->map(function ($permission) {
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

        return $this->successResponse(
            [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'document_number' => $user->document_number,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'document_type' => $user->documentType,
                'role' => $roleData,
            ],
            'Usuario creado exitosamente',
            201
        );
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::with(['documentType', 'roles'])->find($id);

        if (! $user) {
            return $this->errorResponse(
                'Usuario no encontrado',
                ['user' => ['El usuario no existe.']],
                404
            );
        }

        // Formatear rol con permisos
        $role = $user->roles()->first();
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

        return $this->successResponse(
            [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'document_number' => $user->document_number,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'document_type' => $user->documentType,
                'role' => $roleData,
            ],
            'Usuario encontrado',
            200
        );
    }

    /**
     * Update the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse(
                'Usuario no encontrado',
                ['user' => ['El usuario no existe.']],
                404
            );
        }

        // Validación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6',
            'document_number' => 'required|string|max:50',
            'document_type_id' => 'required|exists:document_types,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Validación adicional: documento único por tipo (excluyendo el usuario actual)
        $validator->after(function ($validator) use ($request, $user) {
            if ($request->document_type_id && $request->document_number) {
                $exists = User::where('document_type_id', $request->document_type_id)
                    ->where('document_number', $request->document_number)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'document_number',
                        'Ya existe un usuario con este tipo y número de documento.'
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

        // Actualizar usuario
        $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'document_number' => $request->document_number,
            'document_type_id' => $request->document_type_id,
            'phone' => $request->phone,
            'is_active' => $request->is_active ?? $user->is_active,
        ]);

        // Actualizar contraseña solo si se proporciona
        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // Sincronizar rol
        $role = Role::find($request->role_id);
        if ($role) {
            $user->syncRoles([$role]);
        }

        // Cargar relaciones
        $user->load(['documentType', 'roles']);

        // Formatear rol con permisos
        $roleData = null;
        $userRole = $user->roles()->first();
        if ($userRole) {
            $roleData = [
                'id' => $userRole->id,
                'name' => $userRole->name,
                'guard_name' => $userRole->guard_name,
                'created_at' => $userRole->created_at,
                'updated_at' => $userRole->updated_at,
                'permissions' => $userRole->permissions->map(function ($permission) {
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

        return $this->successResponse(
            [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'document_number' => $user->document_number,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'document_type' => $user->documentType,
                'role' => $roleData,
            ],
            'Usuario actualizado exitosamente',
            200
        );
    }

    /**
     * Remove the specified user (soft delete).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse(
                'Usuario no encontrado',
                ['user' => ['El usuario no existe.']],
                404
            );
        }

        $user->delete();

        return $this->successResponse(
            null,
            'Usuario eliminado exitosamente',
            200
        );
    }
}
