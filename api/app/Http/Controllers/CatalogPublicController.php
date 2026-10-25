<?php

namespace App\Http\Controllers;

use App\Services\CatalogCartTotalsService;
use App\Services\CatalogPublicCartMutationService;
use App\Services\CatalogPublicPlaceOrderService;
use App\Services\CatalogPublicCartLinesService;
use App\Services\CatalogPublicCatalogService;
use App\Services\CatalogPublicMultiCartService;
use App\Services\CatalogPublicOrdersService;
use App\Services\CatalogPublicSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

class CatalogPublicController extends Controller
{
    public function __construct(
        private readonly CatalogPublicSessionService $sessionService,
        private readonly CatalogCartTotalsService $totalsService,
        private readonly CatalogPublicCartLinesService $linesService,
        private readonly CatalogPublicCatalogService $catalogService,
        private readonly CatalogPublicCartMutationService $cartMutationService,
        private readonly CatalogPublicPlaceOrderService $placeOrderService,
        private readonly CatalogPublicOrdersService $ordersService,
        private readonly CatalogPublicMultiCartService $multiCartService,
    ) {
    }

    public function session(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        $expired = $this->sessionService->isExpired($cart);

        if ($request->boolean('touch') && ! $expired) {
            $this->sessionService->touchSession($cart);
            $cart = $this->refreshCartRow($cart);
        }

        $payload = $this->sessionService->buildSessionPayload($cart, $expired);

        if ($expired) {
            return response()->json($payload, 410);
        }

        return response()->json($payload);
    }

    public function totals(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($this->sessionService->isExpired($cart)) {
            return response()->json([
                'valid' => false,
                'reason' => 'expired',
                'message' => 'El tiempo del catálogo expiró.',
            ], 410);
        }

        return response()->json($this->totalsService->totalsForCart($cart));
    }

    public function lines(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($this->sessionService->isExpired($cart)) {
            return response()->json([
                'valid' => false,
                'reason' => 'expired',
                'message' => 'El tiempo del catálogo expiró.',
            ], 410);
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');

        return response()->json([
            'data' => $this->linesService->linesForCart($cart, $token),
        ]);
    }

    public function patchSession(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($this->sessionService->isExpired($cart)) {
            return response()->json([
                'valid' => false,
                'reason' => 'expired',
                'message' => 'El tiempo del catálogo expiró.',
            ], 410);
        }

        $patch = $request->only(['view', 'status_cart', 'expire']);
        $this->sessionService->patchSession($cart, $patch);
        $cart = $this->refreshCartRow($cart);

        return response()->json($this->sessionService->buildSessionPayload($cart, false));
    }

    public function products(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');

        return response()->json($this->catalogService->products($cart, $token, $request));
    }

    public function productCount(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        return response()->json([
            'total' => $this->catalogService->productCount($cart, $request),
        ]);
    }

    public function productDetail(Request $request, int $productId): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');
        $result = $this->catalogService->productDetail($cart, $token, $productId);
        if (empty($result['success'])) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    public function categoriesOrder(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        return response()->json([
            'data' => $this->catalogService->categoriesOrder($cart),
        ]);
    }

    public function categoriesList(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        return response()->json([
            'data' => $this->catalogService->categoriesList($cart),
        ]);
    }

    public function upsertLine(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');
        $productId = (int) $request->input('product_id', 0);
        if ($productId < 1) {
            return response()->json(['success' => false, 'error' => 'product_id_required'], 422);
        }

        $qty = $request->has('qty') ? (int) $request->input('qty') : null;
        $comment = $request->has('comment') ? (string) $request->input('comment') : null;

        $result = $this->cartMutationService->setLineQty($cart, $token, $productId, $qty, $comment);
        if (empty($result['success'])) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function addLine(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');
        $productId = (int) $request->input('product_id', $request->input('id', 0));
        if ($productId < 1) {
            return response()->json(['success' => false, 'error' => 'product_id_required'], 422);
        }

        return response()->json($this->cartMutationService->incrementLine($cart, $token, $productId));
    }

    public function removeLine(Request $request, int $productId): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');

        return response()->json($this->cartMutationService->removeLine($cart, $token, $productId));
    }

    public function clearLines(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');

        return response()->json($this->cartMutationService->clearCart($cart, $token));
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $token = (string) $request->attributes->get('catalog_cart_token', '');
        $input = $request->only(['proforma', 'internal_comments', 'new_address', 'notes', 'source']);
        $result = $this->placeOrderService->place($cart, $token, $input);

        if (empty($result['success'])) {
            $status = (int) ($result['http_status'] ?? 422);

            return response()->json($result, $status);
        }

        return response()->json($result);
    }

    public function orders(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 10)));
        $result = $this->ordersService->ordersList($cart, $page, $perPage);

        if (empty($result['success'])) {
            $status = ($result['error'] ?? '') === 'expired' ? 410 : 422;

            return response()->json($result, $status);
        }

        return response()->json($result);
    }

    public function orderDetail(Request $request, int $orderId): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        if ($response = $this->guardNotExpired($cart)) {
            return $response;
        }

        $result = $this->ordersService->orderDetail($cart, $orderId);
        if (empty($result['success'])) {
            $status = ($result['error'] ?? '') === 'expired' ? 410 : 404;

            return response()->json($result, $status);
        }

        return response()->json($result);
    }

    public function multiCartActiveToken(Request $request): JsonResponse
    {
        $cart = $this->cartFromRequest($request);
        $token = (string) $request->attributes->get('catalog_cart_token', '');
        $result = $this->multiCartService->resolveActiveToken($cart, $token);

        if (empty($result['success'])) {
            $status = ($result['error'] ?? '') === 'expired' ? 410 : 422;

            return response()->json($result, $status);
        }

        return response()->json($result);
    }

    private function guardNotExpired(stdClass $cart): ?JsonResponse
    {
        if (! $this->sessionService->isExpired($cart)) {
            return null;
        }

        return response()->json([
            'valid' => false,
            'reason' => 'expired',
            'message' => 'El tiempo del catálogo expiró.',
        ], 410);
    }

    private function cartFromRequest(Request $request): stdClass
    {
        /** @var stdClass $cart */
        $cart = $request->attributes->get('catalog_cart_row');

        return $cart;
    }

    private function refreshCartRow(stdClass $cart): stdClass
    {
        $cartId = (int) ($cart->cart_id ?? 0);
        if ($cartId < 1) {
            return $cart;
        }

        $fresh = \Illuminate\Support\Facades\DB::table('catalog_cart')
            ->where('cart_id', $cartId)
            ->first();

        return $fresh ?? $cart;
    }
}
