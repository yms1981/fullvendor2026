<?php

namespace App\Services\AppSalesman;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Warehouseorder;
use App\Support\CurrencySchemaHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Réplica del legado REST orderList_post / orderListCompany_post
 * (mismo payload vía vista warehouseorders).
 */
class AppSalesmanOrderListService
{
    /**
     * Órdenes de un cliente (legado orderList_post).
     *
     * @return array{status: string, language_id?: int|string, user_id?: int|string, order_list?: array<int, array<string, mixed>>, error?: string}
     */
    public function listForCustomer(int $customerId): array
    {
        /** @var Collection<int, Warehouseorder> $orders */
        $orders = Warehouseorder::query()
            ->where('customer_id', $customerId)
            ->where('tipo_d', 'D')
            ->orderByDesc('created')
            ->get();

        return $this->buildOrderList($orders);
    }

    /**
     * Órdenes de una compañía (legado orderListCompany_post).
     * Solo del mes en curso (por fecha `created`).
     *
     * @return array{status: string, language_id?: int|string, user_id?: int|string, order_list?: array<int, array<string, mixed>>, error?: string}
     */
    public function listForCompany(int $companyId): array
    {
        $from = Carbon::now()->startOfMonth()->startOfDay();
        $to = Carbon::now()->endOfMonth()->endOfDay();

        /** @var Collection<int, Warehouseorder> $orders */
        $orders = Warehouseorder::query()
            ->where('company_id', $companyId)
            ->where('tipo_d', 'D')
            ->whereBetween('created', [$from, $to])
            ->orderByDesc('created')
            ->get();

        return $this->buildOrderList($orders);
    }

    /**
     * Órdenes del vendedor logueado (mes en curso).
     * Resuelve `sellers.id` desde `user_id` (+ company_id) y filtra
     * `orders.seller_id` (en la vista warehouseorders = columna `user_id`).
     *
     * @return array{status: string, language_id?: int|string, user_id?: int|string, seller_id?: int, order_list?: array<int, array<string, mixed>>, error?: string}
     */
    public function listForSeller(int $companyId, int $userId, ?int $sellerId = null): array
    {
        $resolvedSellerId = $sellerId !== null && $sellerId > 0
            ? $sellerId
            : $this->resolveSellerId($companyId, $userId);

        if ($resolvedSellerId <= 0) {
            return [
                'status' => '0',
                'error' => 'seller_not_found',
            ];
        }

        $from = Carbon::now()->startOfMonth()->startOfDay();
        $to = Carbon::now()->endOfMonth()->endOfDay();

        /** @var Collection<int, Warehouseorder> $orders */
        $orders = Warehouseorder::query()
            ->where('company_id', $companyId)
            ->where('tipo_d', 'D')
            // warehouseorders: orders.seller_id AS user_id
            ->where('user_id', $resolvedSellerId)
            ->whereBetween('created', [$from, $to])
            ->orderByDesc('created')
            ->get();

        $result = $this->buildOrderList($orders);
        if (($result['status'] ?? '0') === '1') {
            $result['seller_id'] = $resolvedSellerId;
            $result['user_id'] = $userId;
        }

        return $result;
    }

    /**
     * users.id → sellers.id (misma compañía). Fallback: user_id ya es sellers.id.
     */
    private function resolveSellerId(int $companyId, int $userId): int
    {
        if ($companyId <= 0 || $userId <= 0) {
            return 0;
        }

        $byUser = Vendor::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->value('id');
        if ($byUser) {
            return (int) $byUser;
        }

        $bySellerPk = Vendor::query()
            ->where('company_id', $companyId)
            ->where('id', $userId)
            ->value('id');

        return $bySellerPk ? (int) $bySellerPk : 0;
    }

    /**
     * @param  Collection<int, Warehouseorder>  $orders
     * @return array{status: string, language_id?: int|string, user_id?: int|string, order_list?: array<int, array<string, mixed>>, error?: string}
     */
    private function buildOrderList(Collection $orders): array
    {
        if ($orders->isEmpty()) {
            return [
                'status' => '0',
                'error' => 'No Order found.',
            ];
        }

        $orderIds = $orders->pluck('order_id')->map(fn ($id) => (int) $id)->all();
        $customerIds = $orders->pluck('customer_id')->unique()->filter()->map(fn ($id) => (int) $id)->all();

        $detailsByOrder = OrderDetail::query()
            ->whereIn('order_id', $orderIds)
            ->get()
            ->groupBy('order_id');

        $productIds = $detailsByOrder
            ->flatten(1)
            ->pluck('product_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        $products = Product::query()
            ->whereIn('pro_id', $productIds)
            ->get(['pro_id', 'product_id', 'name', 'sku', 'barcode', 'currency_type'])
            ->keyBy('pro_id');

        $customers = Customer::query()
            ->whereIn('customer_id', $customerIds)
            ->get(['customer_id', 'business_name', 'name', 'email', 'phone'])
            ->keyBy('customer_id');

        $currencySymbols = $this->preloadCurrencySymbols($products);
        $orderList = [];
        $lastLanguageId = null;
        $lastUserId = null;

        foreach ($orders as $cart) {
            $customer = $customers->get((int) $cart->customer_id);
            $productListRows = $detailsByOrder->get((int) $cart->order_id, collect());
            $subTotal = 0.0;
            $newarray = [];

            foreach ($productListRows as $pr) {
                $productInfo = $products->get((int) $pr->product_id);
                // tipo_d = D: qty negativa → ABS (misma regla que el resto del sistema).
                $qty = abs((float) $pr->qty);
                $lineTotal = $qty * ((float) $pr->sale_price);
                $subTotal += $lineTotal;

                $newarray[] = [
                    'order_id' => $cart->order_id,
                    'currency_type' => $this->symbolFor($productInfo?->currency_type, $currencySymbols),
                    'product_id' => $pr->product_id,
                    'name' => strtoupper((string) ($productInfo?->name ?? '')),
                    'sku' => $productInfo?->sku ?? '',
                    'qty' => number_format($qty, 2),
                    'sale_price' => number_format((float) $pr->sale_price, 2),
                    'fob_price' => number_format((float) $pr->fob_price, 2),
                    'purchase_price' => number_format((float) $pr->purchase_price, 2),
                    'barcode' => $productInfo?->barcode ?? '',
                    'discount' => number_format((float) $pr->discount, 2),
                    'discount_type' => $pr->discount_type,
                    'comment' => $pr->comment ?? '',
                    'created' => $this->formatDate($pr->created),
                ];
            }

            $discount = (float) ($cart->discount ?? 0);

            $orderList[] = [
                'tipo_d' => $cart->tipo_d ?? '',
                'order_id' => $cart->order_id,
                'order_number' => $cart->order_number ?? '',
                'order_comments' => $cart->order_comments ?? '',
                'ordered_total' => number_format($subTotal - $discount, 2),
                'discount' => number_format($discount, 2),
                'discount_a' => number_format((float) ($cart->discount_a ?? 0), 2),
                'amount' => number_format($subTotal, 2),
                'total_amount' => number_format($subTotal - $discount, 2),
                'discount_type' => $cart->discount_type,
                'business_name' => $customer?->business_name ?? '',
                'customer_id' => $customer?->customer_id ?? $cart->customer_id,
                'name' => $customer?->name ?? '',
                'email' => $customer?->email ?? '',
                'phone' => $customer?->phone ?? '',
                'created' => $this->formatDate($cart->created),
                'updated' => $this->formatDate($cart->updated),
                'name_status_spanish' => $cart->name_status_spanish ?? '',
                'name_status_english' => $cart->name_status_english ?? '',
                'scolor' => $cart->color ?? '',
                'warehouse_user_id' => $cart->warehouse_user_id ?? '',
                'warehouse_assign_date' => $this->formatDate($cart->warehouse_assign_date),
                'warehouse_name' => $cart->warehouse_name ?? '',
                'product_list' => $newarray,
            ];

            $lastLanguageId = $cart->language_id;
            $lastUserId = $cart->user_id;
        }

        return [
            'status' => '1',
            'language_id' => $lastLanguageId,
            'user_id' => $lastUserId,
            'order_list' => $this->nullsToEmpty($orderList),
        ];
    }

    /**
     * @param  Collection<int|string, Product>  $products
     * @return array<string|int, string>
     */
    private function preloadCurrencySymbols(Collection $products): array
    {
        $ids = [];
        foreach ($products as $product) {
            $id = $product->currency_type ?? null;
            if ($id !== null && $id !== '') {
                $ids[(string) $id] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $pk = CurrencySchemaHelper::primaryKeyColumn();
        $map = [];
        Currency::query()
            ->whereIn($pk, array_values($ids))
            ->get([$pk, 'symbol'])
            ->each(function ($row) use ($pk, &$map) {
                $map[(string) $row->{$pk}] = (string) ($row->symbol ?: '$');
            });

        return $map;
    }

    /**
     * @param  array<string|int, string>  $symbols
     */
    private function symbolFor(mixed $currencyId, array $symbols): string
    {
        if ($currencyId === null || $currencyId === '') {
            return '$';
        }

        return $symbols[(string) $currencyId] ?? '$';
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $array
     * @return array<int, array<string, mixed>>
     */
    private function nullsToEmpty(array $array): array
    {
        array_walk_recursive($array, static function (&$item) {
            if ($item === null) {
                $item = '';
            }
        });

        return $array;
    }
}
