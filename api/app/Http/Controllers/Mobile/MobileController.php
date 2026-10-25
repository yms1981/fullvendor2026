<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controlador base para API móvil v3
 *
 * Proporciona métodos comunes y respuestas optimizadas para apps móviles.
 * No extiende el Controller CRUD de la API v2 (evita conflictos de firma en index/store/…).
 */
abstract class MobileController extends BaseController
{
    /**
     * Formato estándar de respuesta exitosa para móvil
     * 
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function success($data = null, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Formato estándar de respuesta de error para móvil
     * 
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function error(string $message, $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Respuesta paginada optimizada para móvil
     * 
     * @param mixed $data
     * @param array $meta
     * @return JsonResponse
     */
    protected function paginated($data, array $meta = []): JsonResponse
    {
        return $this->success([
            'items' => $data,
            'meta' => array_merge([
                'total' => 0,
                'per_page' => 20,
                'current_page' => 1,
                'last_page' => 1,
            ], $meta),
        ]);
    }
    
    /**
     * Validar y obtener el company_id del request
     * 
     * @param Request $request
     * @return int|null
     */
    protected function getCompanyId(Request $request): ?int
    {
        $companyId = $request->input('company_id') 
            ?? $request->route('companyId')
            ?? $request->user()?->companiesForRole('manager')->first()?->company_id;
        
        return $companyId ? (int) $companyId : null;
    }
    
    /**
     * Validar y obtener el customer_id del request
     * 
     * @param Request $request
     * @return int|null
     */
    protected function getCustomerId(Request $request): ?int
    {
        $customerId = $request->input('customer_id')
            ?? $request->route('customerId')
            ?? $request->user()?->customer?->customer_id;
        
        return $customerId ? (int) $customerId : null;
    }
}

