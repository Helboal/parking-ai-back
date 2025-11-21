<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Parking AI - API Documentation",
 *     description="API REST para el sistema de gestión de parqueaderos multi-sucursal. Permite administrar sucursales, usuarios, vehículos, tarifas, descuentos, impuestos y operaciones de entrada/salida de vehículos.",
 *
 *     @OA\Contact(
 *         name="Soporte API",
 *         email="soporte@parking-ai.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8001",
 *     description="Servidor de Desarrollo Local"
 * )
 * @OA\Server(
 *     url="http://localhost:8001/api",
 *     description="API Base URL"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token de autenticación Laravel Sanctum. Obtén el token usando el endpoint /api/login"
 * )
 *
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para autenticación y gestión de sesión"
 * )
 * @OA\Tag(
 *     name="Tipos de Documento",
 *     description="Gestión de tipos de documento (CC, NIT, CE, Pasaporte, etc.)"
 * )
 * @OA\Tag(
 *     name="Usuarios",
 *     description="Gestión de usuarios del sistema"
 * )
 * @OA\Tag(
 *     name="Sucursales",
 *     description="Gestión de sucursales/sedes del parqueadero"
 * )
 * @OA\Tag(
 *     name="Tipos de Vehículo",
 *     description="Gestión de tipos de vehículo (Carro, Moto, etc.)"
 * )
 * @OA\Tag(
 *     name="Tipos de Entrada",
 *     description="Gestión de tipos de entrada al parqueadero"
 * )
 * @OA\Tag(
 *     name="Tipos de Suscripción",
 *     description="Gestión de tipos de suscripción (Mensual, Trimestral, etc.)"
 * )
 * @OA\Tag(
 *     name="Métodos de Pago",
 *     description="Gestión de métodos de pago disponibles"
 * )
 * @OA\Tag(
 *     name="Impuestos",
 *     description="Gestión de impuestos del sistema"
 * )
 * @OA\Tag(
 *     name="Capacidad por Sucursal",
 *     description="Configuración de capacidad de parqueadero por sucursal y tipo de vehículo"
 * )
 * @OA\Tag(
 *     name="Tarifas por Sucursal",
 *     description="Configuración de tarifas por minuto por sucursal y tipo de vehículo"
 * )
 * @OA\Tag(
 *     name="Descuentos por Sucursal",
 *     description="Configuración de descuentos por tiempo de permanencia"
 * )
 * @OA\Tag(
 *     name="Tarifas Planas",
 *     description="Configuración de tarifas fijas después de umbral de tiempo"
 * )
 * @OA\Tag(
 *     name="Impuestos por Sucursal",
 *     description="Asignación de impuestos a sucursales"
 * )
 * @OA\Tag(
 *     name="Asignaciones Usuario-Sucursal",
 *     description="Gestión de asignación de usuarios a sucursales"
 * )
 */
abstract class Controller
{
    //
}
