<?php

namespace App\Http\Controllers;

use App\Services\OrderEmailTokenService;
use Illuminate\Http\JsonResponse;

/**
 * Confirmar / rechazar orden desde enlace del correo (público, sin Bearer).
 */
class OrderEmailTokenController extends Controller
{
    public function confirm(string $token, OrderEmailTokenService $service): JsonResponse
    {
        return $this->respond($token, $service->confirm($token));
    }

    public function reject(string $token, OrderEmailTokenService $service): JsonResponse
    {
        return $this->respond($token, $service->reject($token));
    }

    /**
     * @param array{result: string, company_id: int|null, language_id: int|null, order_meta: array<string, string>} $payload
     */
    private function respond(string $token, array $payload): JsonResponse
    {
        return response()->json(['data' => $payload]);
    }
}
