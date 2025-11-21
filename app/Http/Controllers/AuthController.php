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
     * Autenticar usuario y generar token de acceso
     *
     * Permite a un usuario iniciar sesión en el sistema mediante email y contraseña.
     * Valida que el usuario esté activo y retorna un token de autenticación junto
     * con los datos del usuario, su rol y permisos asignados.
     *
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Autenticación"},
     *     summary="Iniciar sesión",
     *     description="Autentica un usuario y genera un token de acceso",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="admin@parking.com", description="Email del usuario"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Contraseña (mínimo 6 caracteres)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login exitoso"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Juan"),
     *                     @OA\Property(property="last_name", type="string", example="Pérez"),
     *                     @OA\Property(property="email", type="string", example="admin@parking.com"),
     *                     @OA\Property(property="document_number", type="string", example="1234567890"),
     *                     @OA\Property(property="phone", type="string", example="3001234567"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|abcd1234...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales incorrectas",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Credenciales incorrectas")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Usuario inactivo",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Usuario inactivo")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errores de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * Cerrar sesión y revocar token de acceso
     *
     * Invalida el token de autenticación actual del usuario, cerrando su sesión
     * de forma segura. El token no podrá ser utilizado nuevamente.
     *
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Autenticación"},
     *     summary="Cerrar sesión",
     *     description="Revoca el token del usuario autenticado",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout exitoso"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
     * Obtener información del usuario autenticado
     *
     * Retorna los datos completos del usuario actualmente autenticado,
     * incluyendo su tipo de documento, rol y permisos asignados.
     *
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Autenticación"},
     *     summary="Obtener usuario autenticado",
     *     description="Retorna los datos del usuario con sesión activa",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Datos del usuario",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuario autenticado"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan"),
     *                 @OA\Property(property="last_name", type="string", example="Pérez"),
     *                 @OA\Property(property="email", type="string", example="admin@parking.com"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
