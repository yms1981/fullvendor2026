<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Company;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sincroniza carritos abiertos (Open Cart) al crear una orden desde backend o catálogo.
 * Si la orden se termina, el carrito se retira; si no, permanece en Open Cart.
 */
final class OpenCartOrderSyncService
{
    public const STATUS_OPEN = 1;
    public const STATUS_VIEWED = 2;
    public const STATUS_WITH_ITEMS = 3;
    public const STATUS_COMPLETED = 4;

    public const TYPE_COMPANY = 'to_company';

    /**
     * Crea o actualiza un carrito Open Cart (type to_company) con las líneas actuales de la grilla.
     *
     * @param list<array{pro_id?: int, product_id?: int, qty?: float|int|string, price?: float|int|string, sale_price?: float|int|string, comment?: string}> $products
     * @return array{cart_id: int, token_kor: string, status_cart: int}
     */
    public function syncCompanyDraft(
        int $companyId,
        int $customerId,
        array $products,
        ?int $cartId = null,
        ?int $sellerUserId = null
    ): array {
        if (! Schema::hasTable('catalog_cart')) {
            return ['cart_id' => 0, 'token_kor' => '', 'status_cart' => 0];
        }

        $cart = $this->resolveCompanyDraftCart($companyId, $customerId, $cartId, $sellerUserId);
        $token = (string) ($cart->token_kor ?? '');
        $resolvedCartId = (int) ($cart->cart_id ?? 0);

        $normalized = $this->normalizeProducts($products);
        $this->replaceDetailLines($companyId, $customerId, $resolvedCartId, $token, $normalized);

        $status = $normalized === [] ? self::STATUS_VIEWED : self::STATUS_WITH_ITEMS;
        DB::table('catalog_cart')
            ->where('cart_id', $resolvedCartId)
            ->where('company_id', $companyId)
            ->update(['status_cart' => $status]);

        return [
            'cart_id' => $resolvedCartId,
            'token_kor' => $token,
            'status_cart' => $status,
        ];
    }

    /**
     * Retira el carrito de Open Cart cuando la orden se completa.
     */
    public function closeCompleted(
        int $companyId,
        ?int $cartId = null,
        ?string $token = null,
        ?int $orderId = null,
        ?int $customerId = null
    ): void {
        if (! Schema::hasTable('catalog_cart') || $companyId < 1) {
            return;
        }

        $query = DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at');

        if ($cartId !== null && $cartId > 0) {
            $query->where('cart_id', $cartId);
        } elseif ($token !== null && $token !== '') {
            $query->where('token_kor', $token);
        } elseif ($customerId !== null && $customerId > 0) {
            $query->where('customer_id', $customerId)
                ->where('type_cart', self::TYPE_COMPANY)
                ->where(function ($q) {
                    $q->whereNull('status_cart')
                        ->orWhere('status_cart', '!=', self::STATUS_COMPLETED);
                });
        } else {
            return;
        }

        $updates = [
            'status_cart' => self::STATUS_COMPLETED,
            'disable_total' => 1,
            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        if ($orderId !== null && $orderId > 0) {
            $updates['order_id'] = $orderId;
        }

        $query->update($updates);
    }

    /**
     * Marca como completado y elimina (soft) un carrito ya localizado.
     */
    public function closeCartRow(int $cartId, ?int $orderId = null): void
    {
        if (! Schema::hasTable('catalog_cart') || $cartId < 1) {
            return;
        }

        $updates = [
            'status_cart' => self::STATUS_COMPLETED,
            'disable_total' => 1,
            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        if ($orderId !== null && $orderId > 0) {
            $updates['order_id'] = $orderId;
        }

        DB::table('catalog_cart')
            ->where('cart_id', $cartId)
            ->whereNull('deleted_at')
            ->update($updates);
    }

    private function resolveCompanyDraftCart(
        int $companyId,
        int $customerId,
        ?int $cartId,
        ?int $sellerUserId
    ): object {
        if ($cartId !== null && $cartId > 0) {
            $existing = DB::table('catalog_cart')
                ->where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->where('cart_id', $cartId)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('status_cart')
                        ->orWhere('status_cart', '!=', self::STATUS_COMPLETED);
                })
                ->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $existing = DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('type_cart', self::TYPE_COMPANY)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('status_cart')
                    ->orWhere('status_cart', '!=', self::STATUS_COMPLETED);
            })
            ->orderByDesc('cart_id')
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        return $this->createCompanyDraftCart($companyId, $customerId, $sellerUserId);
    }

    private function createCompanyDraftCart(int $companyId, int $customerId, ?int $sellerUserId): object
    {
        $company = Company::query()->findOrFail($companyId);
        $customer = Customer::query()
            ->where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($sellerUserId === null || $sellerUserId <= 0) {
            $seller = $customer->sellers()->first() ?? $customer->company?->defaultSeller;
            $userId = $seller?->user_id;
            $sellerUserId = ($userId !== null && (int) $userId > 0) ? (int) $userId : null;
        }

        $catalog = Catalog::query()
            ->where('company_id', $companyId)
            ->where('default_catalog', 1)
            ->first()
            ?? Catalog::query()->where('company_id', $companyId)->orderBy('catalog_id')->first();

        $expirationDays = (int) ($company->catalogsend_time ?? 0);
        if ($expirationDays < 1) {
            $expirationDays = 15;
        }

        $tokenKor = app(LegacyCatalogCartTokenService::class)->generateUniqueEncrypted();
        $now = Carbon::now();
        $cartNumber = '';
        $chars = '123456789';
        for ($i = 0; $i < 6; $i++) {
            $cartNumber .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $insert = [
            'customer_id' => $customer->customer_id,
            'created' => $now->format('Y-m-d H:i:s'),
            'user_id' => $sellerUserId,
            'company_id' => $companyId,
            'catalog_id' => $catalog !== null ? (int) $catalog->catalog_id : null,
            'expired_date' => $now->copy()->addDays($expirationDays)->format('Y-m-d H:i:s'),
            'token_kor' => $tokenKor,
            'order_sent' => 1,
            'status_cart' => self::STATUS_OPEN,
            'type_cart' => self::TYPE_COMPANY,
            'cart_number' => $cartNumber,
        ];

        $newId = (int) DB::table('catalog_cart')->insertGetId($insert);

        return (object) array_merge($insert, ['cart_id' => $newId]);
    }

    /**
     * @param list<array<string, mixed>> $products
     * @return list<array{product_id: int, qty: float, sale_price: float, comment: string}>
     */
    private function normalizeProducts(array $products): array
    {
        $out = [];
        foreach ($products as $item) {
            if (! is_array($item)) {
                continue;
            }
            $productId = (int) ($item['pro_id'] ?? $item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            if ($productId < 1 || $qty <= 0) {
                continue;
            }
            $out[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'sale_price' => (float) ($item['price'] ?? $item['sale_price'] ?? 0),
                'comment' => (string) ($item['comment'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param list<array{product_id: int, qty: float, sale_price: float, comment: string}> $lines
     */
    private function replaceDetailLines(
        int $companyId,
        int $customerId,
        int $cartId,
        string $token,
        array $lines
    ): void {
        if (! Schema::hasTable('detail_car') || $cartId < 1) {
            return;
        }

        DB::table('detail_car')->where('cart_id', $cartId)->delete();
        if ($token !== '') {
            DB::table('detail_car')->where('token_car', $token)->delete();
        }

        if ($lines === []) {
            return;
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = [
                'company_id' => $companyId,
                'cart_id' => $cartId,
                'customer_id' => $customerId,
                'token_car' => $token,
                'product_id' => $line['product_id'],
                'qty' => $line['qty'],
                'sale_price' => $line['sale_price'],
                'comment' => $line['comment'],
                'created' => $now,
            ];
        }
        DB::table('detail_car')->insert($rows);
    }
}
