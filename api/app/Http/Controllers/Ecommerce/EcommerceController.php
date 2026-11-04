<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Services\Ecommerce\EcommerceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sección ecommerce: token fijo de la tienda (header X-Api-Key).
 */
class EcommerceController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => 'ecommerce ok',
        ]);
    }

    public function storeOrder(Request $request, EcommerceOrderService $orders): JsonResponse
    {
        $data = $request->json()->all();
        if ($data === []) {
            $data = $request->all();
        }

        try {
            $placed = $orders->place(is_array($data) ? $data : []);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => '0',
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            Log::error('ecommerce.orders.store failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => '0',
                'success' => false,
                'error' => true,
                'message' => 'No se pudo crear el pedido.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $placed['duplicate'] ? 'Pedido ya registrado.' : 'Pedido creado.',
            'order_id' => $placed['order_id'],
            'order_number' => $placed['order_number'],
            'uuid' => $placed['uuid'],
        ], $placed['duplicate'] ? Response::HTTP_OK : Response::HTTP_CREATED);
    }
}
