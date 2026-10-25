<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación de la sección AppSalesman por cabecera fija X-Api-Key.
 */
class ValidateAppSalesmanApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = trim((string) config('app_salesman.api_key', ''));
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
                'message' => $this->msg('unauthorized'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    private function msg(string $key): string
    {
        $locale = strtolower((string) request()->header(
            'Accept-Language',
            request()->header('X-App-Locale', 'en')
        ));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return $data['cmp-app-salesman'][$key] ?? $key;
    }
}
