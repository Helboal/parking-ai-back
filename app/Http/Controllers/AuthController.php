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
     * @param Request $request
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

        // Buscar usuario
        $user = User::where('email', $request->email)->first();

        // Verificar credenciales
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse(
                'Credenciales incorrectas',
                ['email' => ['Las credenciales proporcionadas son incorrectas.']],
                401
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
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
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
     * @param Request $request
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        // Obtener usuario autenticado
        $user = $request->user();

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
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'role' => $roleData,
            ],
            'Usuario autenticado',
            200
        );
    }
}
