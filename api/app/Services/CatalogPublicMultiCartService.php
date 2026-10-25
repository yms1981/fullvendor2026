<?php

namespace App\Services;

use App\Models\StatusCart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Multi-carrito to_customer: resuelve el token del carrito hijo activo (legacy CarritoModel).
 */
final class CatalogPublicMultiCartService
{
    public function __construct(
        private readonly CatalogPublicSessionService $sessionService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveActiveToken(stdClass $cart, string $currentToken): array
    {
        if ($this->sessionService->isExpired($cart)) {
            return [
                'success' => false,
                'error' => 'expired',
                'message' => 'El tiempo del catálogo expiró.',
            ];
        }

        if ((string) ($cart->type_cart ?? '') !== 'to_customer') {
            return [
                'success' => true,
                'token' => $currentToken,
                'redirect' => false,
                'created' => false,
            ];
        }

        $statusRow = $this->statusCartForCart($cart);
        if ($statusRow === null || (int) ($statusRow->multiple_cart ?? 0) !== 1) {
            return [
                'success' => true,
                'token' => $currentToken,
                'redirect' => false,
                'created' => false,
            ];
        }

        $parentCartId = (int) ($cart->cart_id ?? 0);
        if ($parentCartId < 1) {
            return [
                'success' => true,
                'token' => $currentToken,
                'redirect' => false,
                'created' => false,
            ];
        }

        $lastChild = DB::table('catalog_cart')
            ->where('father_id', $parentCartId)
            ->whereNull('deleted_at')
            ->orderByDesc('cart_id')
            ->first();

        if ($lastChild !== null) {
            $childStatus = $this->statusCartForCod($lastChild->status_cart ?? null);
            if ($childStatus !== null && (int) ($childStatus->multiple_cart ?? 0) === 1) {
                $newToken = $this->createChildCart($cart, $parentCartId);

                return [
                    'success' => true,
                    'token' => $newToken,
                    'redirect' => $newToken !== $currentToken,
                    'created' => true,
                ];
            }

            $childToken = (string) ($lastChild->token_kor ?? '');

            return [
                'success' => true,
                'token' => $childToken !== '' ? $childToken : $currentToken,
                'redirect' => $childToken !== '' && $childToken !== $currentToken,
                'created' => false,
            ];
        }

        $newToken = $this->createChildCart($cart, $parentCartId);

        return [
            'success' => true,
            'token' => $newToken,
            'redirect' => $newToken !== $currentToken,
            'created' => true,
        ];
    }

    private function statusCartForCart(stdClass $cart): ?StatusCart
    {
        return $this->statusCartForCod($cart->status_cart ?? null);
    }

    private function statusCartForCod(mixed $cod): ?StatusCart
    {
        if ($cod === null || $cod === '') {
            return null;
        }

        return StatusCart::query()->where('cod', (string) $cod)->first();
    }

    private function createChildCart(stdClass $parentCart, int $parentCartId): string
    {
        $token = $this->generateUniqueHexToken();
        $now = Carbon::now()->format('Y-m-d H:i:s');

        DB::table('catalog_cart')->insert([
            'customer_id' => (int) ($parentCart->customer_id ?? 0),
            'created' => $now,
            'user_id' => $parentCart->user_id ?? null,
            'company_id' => (int) ($parentCart->company_id ?? 0),
            'catalog_id' => (int) ($parentCart->catalog_id ?? 0),
            'expired_date' => $parentCart->expired_date ?? $now,
            'token_kor' => $token,
            'catalog_text' => $parentCart->catalog_text ?? null,
            'status_cart' => 1,
            'type_cart' => (string) ($parentCart->type_cart ?? 'to_customer'),
            'father_id' => $parentCartId,
            'cart_number' => $this->generateCartNumber(),
        ]);

        return $token;
    }

    private function generateUniqueHexToken(int $maxAttempts = 20): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $token = bin2hex(random_bytes(16));
            $exists = DB::table('catalog_cart')->where('token_kor', $token)->exists();
            if (! $exists) {
                return $token;
            }
        }

        return bin2hex(random_bytes(16));
    }

    private function generateCartNumber(int $length = 6): string
    {
        $characters = '123456789';
        $randomString = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }

        return $randomString;
    }
}
