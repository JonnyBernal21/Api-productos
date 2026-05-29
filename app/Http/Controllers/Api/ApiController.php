<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API de productos Axum Technologies',
            'data' => [
                'name' => config('app.name'),
                'version' => '1.0.0',
                'base_url' => url('/api'),
                'documentation' => [
                    'auth' => [
                        'POST /api/auth/register' => 'Registro de usuario',
                        'POST /api/auth/login' => 'Inicio de sesión (devuelve token Bearer)',
                        'POST /api/auth/logout' => 'Cerrar sesión (requiere token)',
                        'GET /api/auth/user' => 'Usuario autenticado (requiere token)',
                    ],
                    'products' => [
                        'GET /api/products' => 'Listar productos (paginado)',
                        'GET /api/products/{id}' => 'Detalle de producto',
                        'POST /api/products' => 'Crear producto (requiere token)',
                        'PUT /api/products/{id}' => 'Actualizar producto (requiere token)',
                        'DELETE /api/products/{id}' => 'Eliminar producto (requiere token)',
                    ],
                ],
            ],
        ]);
    }
}
