<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación de la sección ecommerce por cabecera fija X-Api-Key.
 */
class ValidateEcommerceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = trim((string) config('ecommerce.api_key', ''));
        $provided = trim((string) (
            $request->header('X-Api-Key')
            ?? $request->header('x-api-key')
            ?? ''
        ));

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'status' => '0',
                'success' => false,
                'error' => true,
                'message' => 'Token de ecommerce inválido.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
