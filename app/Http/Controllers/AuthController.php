<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login user and generate token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                $validator->errors(),
                422
            );
        }

        // Buscar usuario con relación document_type
        $user = User::with('documentType')->where('email', $request->email)->first();

        // Verificar credenciales
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse(
                'Credenciales incorrectas',
                ['email' => ['Las credenciales proporcionadas son incorrectas.']],
                401
            );
        }

        // Verificar si el usuario está activo
        if (! $user->is_active) {
            return $this->errorResponse(
                'Usuario inactivo',
                ['user' => ['El usuario está inactivo y no puede iniciar sesión.']],
                403
            );
        }

        // Generar token
        $token = $user->createToken('api-token')->plainTextToken;

        // Obtener rol con permisos
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

        // Respuesta exitosa
        return $this->successResponse(
            [
                'user' => [
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
                'token' => $token,
            ],
            'Login exitoso',
            200
        );
    }

    /**
     * Logout user and revoke token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();

        // Respuesta exitosa
        return $this->successResponse(
            null,
            'Logout exitoso',
            200
        );
    }

    /**
     * Get authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        // Obtener usuario autenticado con relación document_type
        $user = $request->user()->load('documentType');

        // Obtener rol con permisos
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

        // Respuesta exitosa
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
            'Usuario autenticado',
            200
        );
    }
}
