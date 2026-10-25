<?php

namespace App\Services;

use App\Models\StatusCart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

final class OpenCartCompanyService
{
    public function __construct(
        private readonly CatalogCartTotalsService $totalsService,
        private readonly CatalogPublicPlaceOrderService $placeOrderService,
        private readonly CatalogPublicSessionService $sessionService,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(int $companyId, int $cartId): ?array
    {
        $cart = $this->fetchCartRow($companyId, $cartId);
        if ($cart === null) {
            return null;
        }

        $token = (string) ($cart->token_kor ?? '');
        $details = $this->loadDetailLines($companyId, $cartId, $token);
        $totals = $this->totalsService->totalsForCart($cart);
        $expired = $this->sessionService->isExpired($cart);

        $webBase = rtrim((string) config('fullvendor.web_url', config('app.url', 'http://localhost')), '/');
        $link = $token !== ''
            ? $webBase . '/catalogo/producto?token=' . rawurlencode($token)
            : '';

        $customerLabel = trim((string) ($cart->business_name ?? ''));
        $customerName = trim((string) ($cart->customer_name ?? ''));
        if ($customerName !== '') {
            $customerLabel .= $customerLabel !== '' ? ' (' . $customerName . ')' : $customerName;
        }

        return [
            'cart_info' => [
                'cart_id' => (int) $cart->cart_id,
                'company_id' => (int) $cart->company_id,
                'customer_id' => (int) ($cart->customer_id ?? 0),
                'catalog_id' => (int) ($cart->catalog_id ?? 0),
                'created' => $cart->created,
                'token_kor' => $token,
                'phone' => (string) ($cart->phone ?? ''),
                'email_customer' => (string) ($cart->email_customer ?? ''),
                'customer' => $customerLabel,
                'seller' => trim((string) ($cart->seller ?? '')),
                'catalog_name' => (string) ($cart->catalog_name ?? ''),
                'status_cart' => $cart->status_cart,
            ],
            'details_car' => $details,
            'details_total' => [
                'sub_total' => $totals['sub_total'],
                'discount_total' => $totals['discount_total'],
                'total_orden' => $totals['total_orden'],
            ],
            'cart_expired' => $expired,
            'link' => $link,
        ];
    }

    /**
     * @return array{success: bool, msg?: string, order_id?: int}
     */
    public function softDelete(int $companyId, int $cartId): array
    {
        $cart = $this->fetchCartRow($companyId, $cartId);
        if ($cart === null) {
            return ['success' => false, 'msg' => 'not_found'];
        }

        $statusId = $cart->status_cart ?? null;
        if ($statusId !== null && $statusId !== '') {
            $status = StatusCart::query()->where('id_status', (int) $statusId)->first();
            if ($status !== null && (int) ($status->allow_toremove ?? 0) !== 1) {
                return ['success' => false, 'msg' => 'not_deleted'];
            }
        }

        DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->where('cart_id', $cartId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);

        return ['success' => true, 'msg' => 'cart_deleted'];
    }

    /**
     * @return array<string, mixed>
     */
    public function submitOrder(int $companyId, int $cartId): array
    {
        $cart = $this->fetchCartRow($companyId, $cartId);
        if ($cart === null) {
            return ['success' => false, 'msg' => 'not_found'];
        }

        $token = (string) ($cart->token_kor ?? '');
        if ($token === '') {
            return ['success' => false, 'msg' => 'invalid_cart'];
        }

        $result = $this->placeOrderService->place($cart, $token, [
            'source' => 'manual',
        ]);

        if (empty($result['success'])) {
            $code = (string) ($result['error'] ?? 'submit_failed');
            if ($code === '') {
                $code = 'submit_failed';
            }

            return [
                'success' => false,
                'msg' => $code,
                'error' => $code,
            ];
        }

        $orderId = (int) ($result['order_id'] ?? 0);
        $orderNumber = '';
        if ($orderId > 0 && Schema::hasTable('orders')) {
            $orderNumber = (string) (DB::table('orders')->where('order_id', $orderId)->value('order_number') ?? '');
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'msg' => 'orden_created',
        ];
    }

    /**
     * Extiende la fecha de expiración del carrito (notify con link caducado + confirmación).
     *
     * @return array{success: bool, expired_date?: string}
     */
    public function renewExpiration(int $companyId, int $cartId, int $extraDays = 15): array
    {
        $cart = $this->fetchCartRow($companyId, $cartId);
        if ($cart === null) {
            return ['success' => false];
        }

        $days = max(1, $extraDays);
        $newExpiry = Carbon::now()->addDays($days)->format('Y-m-d H:i:s');

        DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->where('cart_id', $cartId)
            ->update(['expired_date' => $newExpiry]);

        return ['success' => true, 'expired_date' => $newExpiry];
    }

    private function fetchCartRow(int $companyId, int $cartId): ?stdClass
    {
        if (! Schema::hasTable('catalog_cart')) {
            return null;
        }

        $query = DB::table('catalog_cart as c')
            ->leftJoin('catalog as cg', 'cg.catalog_id', '=', 'c.catalog_id')
            ->leftJoin('customers as cus', 'cus.customer_id', '=', 'c.customer_id')
            ->where('c.company_id', $companyId)
            ->where('c.cart_id', $cartId)
            ->whereNull('c.deleted_at')
            ->select([
                'c.cart_id',
                'c.company_id',
                'c.customer_id',
                'c.catalog_id',
                'c.created',
                'c.token_kor',
                'c.status_cart',
                'c.expired_date',
                'c.type_cart',
                'c.user_id',
                'cus.phone',
                'cus.email as email_customer',
                'cus.business_name',
                'cus.name as customer_name',
                'cg.catalog_name',
            ]);

        if (Schema::hasTable('users')) {
            $query->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->addSelect(DB::raw("COALESCE(u.name, '') AS seller"));
        } else {
            $query->selectRaw("'' AS seller");
        }

        $row = $query->first();

        return $row !== null ? (object) $row : null;
    }

    /**
     * @return list<object>
     */
    private function loadDetailLines(int $companyId, int $cartId, string $token): array
    {
        if (! Schema::hasTable('detail_car') || ! Schema::hasTable('products')) {
            return [];
        }

        $query = DB::table('detail_car as d')
            ->join('products as p', 'p.pro_id', '=', 'd.product_id')
            ->where('p.company_id', $companyId)
            ->orderBy('d.detail_id');

        if ($token !== '') {
            $query->where('d.token_car', $token);
        } else {
            $query->where('d.cart_id', $cartId);
        }

        $query->select([
                'd.detail_id',
                'p.name',
                'p.status',
                'p.sku',
                'p.barcode',
                'p.pro_id as product_id',
                'p.sale_price',
                'p.minimum_stock',
                'p.notify_minimum_stock',
                'd.comment',
                'd.sale_price as precio_grupo',
                'd.qty',
            ])
                ->selectRaw('(d.qty * d.sale_price) as importe');

        if (Schema::hasTable('product_images')) {
            $query->selectRaw($this->firstProductImageUuidSql().' as image_uuid');
        } else {
            $query->selectRaw('NULL as image_uuid');
        }

        return $query->get()->map(static function ($row) {
            return (object) [
                'name' => (string) ($row->name ?? ''),
                'status' => (int) ($row->status ?? 0),
                'sku' => (string) ($row->sku ?? ''),
                'barcode' => (string) ($row->barcode ?? ''),
                'product_id' => (int) ($row->product_id ?? 0),
                'sale_price' => (float) ($row->sale_price ?? 0),
                'minimum_stock' => (float) ($row->minimum_stock ?? 0),
                'notify_minimum_stock' => (int) ($row->notify_minimum_stock ?? 0),
                'comment' => (string) ($row->comment ?? ''),
                'precio_grupo' => (float) ($row->precio_grupo ?? 0),
                'qty' => (float) ($row->qty ?? 0),
                'importe' => (float) ($row->importe ?? 0),
                'image_uuid' => $row->image_uuid ?? null,
                'images' => null,
            ];
        })->all();
    }

    /**
     * Misma lógica que Product::getProductImageAttribute: default_image_id y luego
     * la primera fila de product_images por img_order (no MIN(uuid) lexicográfico).
     */
    private function firstProductImageUuidSql(): string
    {
        $orderedPi = '(SELECT pi2.image_id FROM product_images pi2
            WHERE pi2.product_id = p.pro_id AND pi2.deleted_at IS NULL
            ORDER BY COALESCE(pi2.img_order, 999999) ASC, pi2.img_id ASC
            LIMIT 1)';

        if (Schema::hasColumn('products', 'default_image_id')) {
            return "COALESCE(NULLIF(TRIM(p.default_image_id), ''), {$orderedPi})";
        }

        return $orderedPi;
    }
}
