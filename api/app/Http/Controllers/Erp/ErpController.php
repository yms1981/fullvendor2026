<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Sección ERP: endpoints autenticados con cabecera X-Api-Key (token fijo).
 */
#[OA\Tag(
    name: 'Erp',
    description: 'Integración ERP. Auth: header X-Api-Key (sin Sanctum).'
)]
class ErpController extends Controller
{
    /**
     * GET /api/v2/erp/ping
     * Comprueba que la API key es válida.
     */
    #[OA\Get(
        path: 'api/v2/erp/ping',
        summary: 'Comprobar autenticación ERP',
        description: 'Responde OK si el header X-Api-Key es válido.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'API key válida',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida o ausente'),
        ]
    )]
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->msg('ping_ok'),
        ]);
    }

    protected function msg(string $key): string
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return $data['cmp-erp'][$key] ?? $key;
    }

    protected function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'status' => '0',
            'success' => false,
            'error' => true,
            'message' => $message,
        ], $status);
    }
}
