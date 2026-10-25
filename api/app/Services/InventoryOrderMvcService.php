<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryOrderMvcService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function categoriesForAddSelect(int $companyId): array
    {
        $pivotQuery = Category::query()
            ->select('categories.cat_id', 'categories.category_name', 'categories.category_default')
            ->selectRaw('COUNT(DISTINCT products.pro_id) as qty_products')
            ->join('category_product as cp', function ($join) {
                $join->on('cp.category_id', '=', 'categories.cat_id');
                if (Schema::hasColumn('category_product', 'deleted_at')) {
                    $join->whereNull('cp.deleted_at');
                }
            })
            ->join('products', function ($join) use ($companyId) {
                $join->on('products.pro_id', '=', 'cp.product_id')
                    ->where('products.company_id', $companyId)
                    ->where('products.status', 1)
                    ->whereNotNull('products.name');
            })
            ->where('categories.company_id', $companyId)
            ->where('categories.category_status', 1)
            ->groupBy('categories.cat_id', 'categories.category_name', 'categories.category_default')
            ->having('qty_products', '>', 0);

        $byId = [];
        foreach ($pivotQuery->get() as $row) {
            $byId[(int) $row->cat_id] = [
                'cat_id' => (int) $row->cat_id,
                'category_name' => (string) $row->category_name,
                'category_default' => $row->category_default,
                'qty_products' => (int) $row->qty_products,
            ];
        }

        $legacyProducts = Product::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereNotNull('name')
            ->whereNotNull('category_id')
            ->where('category_id', '!=', '')
            ->whereDoesntHave('categories')
            ->get(['pro_id', 'category_id']);

        foreach ($legacyProducts as $product) {
            foreach ($this->parseLegacyCategoryIds((string) $product->category_id) as $catId) {
                if (isset($byId[$catId])) {
                    $byId[$catId]['qty_products']++;
                    continue;
                }
                $cat = Category::query()
                    ->where('company_id', $companyId)
                    ->where('category_status', 1)
                    ->where('cat_id', $catId)
                    ->first(['cat_id', 'category_name', 'category_default']);
                if ($cat !== null) {
                    $byId[$catId] = [
                        'cat_id' => (int) $cat->cat_id,
                        'category_name' => (string) $cat->category_name,
                        'category_default' => $cat->category_default,
                        'qty_products' => 1,
                    ];
                }
            }
        }

        $rows = array_values($byId);
        usort($rows, static function (array $a, array $b) {
            return strcasecmp((string) $b['category_name'], (string) $a['category_name']);
        });

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function productsForAdd(int $companyId): array
    {
        $products = Product::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereNotNull('name')
            ->with(['categories' => static function ($q) {
                $q->where('category_status', 1)->select('categories.cat_id', 'category_name', 'categories.image');
            }])
            ->orderBy('name')
            ->get(['pro_id', 'sku', 'name', 'category_id', 'imgURL', 'sale_price']);

        if ($products->isEmpty()) {
            return [];
        }

        $productIds = $products->pluck('pro_id')->map(static fn ($id) => (int) $id)->all();
        $imageMetaByProduct = $this->primaryImageMetaByProductIds($productIds);

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->where('category_status', 1)
            ->get(['cat_id', 'category_name', 'image']);

        $catMap = [];
        foreach ($categories as $cat) {
            $imageRaw = $cat->getAttributes()['image'] ?? $cat->getRawOriginal('image') ?? '';
            if (! is_string($imageRaw)) {
                $imageRaw = '';
            }
            $imageUuid = '';
            if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', trim($imageRaw), $m)) {
                $imageUuid = $m[1];
            }
            $catMap[(int) $cat->cat_id] = [
                'cat_id' => (int) $cat->cat_id,
                'category_name' => (string) $cat->category_name,
                'image_uuid' => $imageUuid,
            ];
        }

        $out = [];
        foreach ($products as $p) {
            $proId = (int) $p->pro_id;
            $catIds = $p->categories->pluck('cat_id')->map(static fn ($id) => (int) $id)->all();
            if ($catIds === []) {
                $catIds = $this->parseLegacyCategoryIds((string) ($p->category_id ?? ''));
            }
            $catIds = array_values(array_unique(array_filter($catIds)));

            $catRows = [];
            $catNames = [];
            foreach ($catIds as $cid) {
                if (! isset($catMap[$cid])) {
                    continue;
                }
                $catRows[] = $catMap[$cid];
                $catNames[] = $catMap[$cid]['category_name'];
            }

            $legacyImg = trim((string) ($p->imgURL ?? ''));
            $meta = $imageMetaByProduct[$proId] ?? ['uuid' => '', 'name' => ''];
            $uuid = trim((string) ($meta['uuid'] ?? ''));
            $imageName = trim((string) ($meta['name'] ?? ''));

            $out[] = [
                'pro_id' => $proId,
                'sku' => (string) ($p->sku ?? ''),
                'name' => (string) ($p->name ?? ''),
                'category_id' => implode(',', $catIds),
                'categories' => $catRows,
                'categories_name' => implode(' | ', $catNames),
                'imagen_principal' => $imageName !== '' ? $imageName : $legacyImg,
                'image_name' => $imageName,
                'image_uuid' => $uuid,
                'legacy_imgURL' => $legacyImg,
                // Vacío a propósito: el MVC resuelve URL con company_product_image_url.
                'image' => '',
                'aditional' => 0,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, int>
     */
    private function parseLegacyCategoryIds(string $categoryCsv): array
    {
        return array_values(array_filter(array_map('intval', explode(',', $categoryCsv))));
    }

    public const MAX_PRODUCTS_PER_CREATE = 500;

    public const ITEMS_PER_ORDER = 20;

    /**
     * Crea una o varias órdenes de inventario, de 20 ítems cada una.
     *
     * @param array<int, int|string> $productIds
     * @return array<int, Order>
     */
    public function createOrders(int $companyId, int $warehouseUserId, array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            throw new \InvalidArgumentException('product_list_required');
        }
        if (count($productIds) > self::MAX_PRODUCTS_PER_CREATE) {
            throw new \InvalidArgumentException('product_list_max');
        }

        $chunks = array_chunk($productIds, self::ITEMS_PER_ORDER);
        $sequence = $this->nextLegacyOrderSequence($companyId);

        return DB::transaction(function () use ($companyId, $warehouseUserId, $chunks, $sequence) {
            $orders = [];
            foreach ($chunks as $index => $chunk) {
                $orderNumber = 'INV'.sprintf('%06d', $sequence + $index);
                $orders[] = $this->createOrder($companyId, $warehouseUserId, $chunk, $orderNumber);
            }

            return $orders;
        });
    }

    /**
     * @param array<int, int|string> $productIds
     */
    public function createOrder(int $companyId, int $warehouseUserId, array $productIds, ?string $orderNumber = null): Order
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            throw new \InvalidArgumentException('product_list_required');
        }

        $customer = Customer::query()
            ->where('company_id', $companyId)
            ->orderBy('customer_id')
            ->first();

        if ($customer === null) {
            throw new \InvalidArgumentException('customer_required');
        }

        $statusId = StatusOrder::getIdByCode(OrderStatusCodes::PENDING);
        if ($statusId === false) {
            $statusId = 1;
        }

        return DB::transaction(function () use ($companyId, $warehouseUserId, $productIds, $customer, $statusId, $orderNumber) {
            if ($orderNumber === null || $orderNumber === '') {
                $orderNumber = $this->generateLegacyOrderNumber($companyId);
            }
            $now = now();

            $orderData = [
                'company_id' => $companyId,
                'customer_id' => $customer->customer_id,
                'order_number' => $orderNumber,
                'tipo_d' => 'I',
                'source' => 'manual',
                'status_id' => (int) $statusId,
                'discount' => 0,
                'discount_type' => 1,
                'created' => $now,
                'updated' => $now,
            ];

            if (Schema::hasColumn('orders', 'language_id')) {
                $company = Company::query()->where('company_id', $companyId)->first();
                $orderData['language_id'] = (int) ($company->language_id ?? 1);
            }
            if (Schema::hasColumn('orders', 'warehouse_user_id') && $warehouseUserId > 0) {
                $orderData['warehouse_user_id'] = $warehouseUserId;
            }

            $order = Order::create($orderData);

            $products = Product::query()
                ->where('company_id', $companyId)
                ->whereIn('pro_id', $productIds)
                ->get(['pro_id', 'sale_price', 'fob_price', 'purchase_price'])
                ->keyBy('pro_id');

            foreach ($productIds as $proId) {
                $product = $products->get($proId);
                OrderDetail::create([
                    'order_id' => $order->order_id,
                    'product_id' => $proId,
                    'qty' => -1,
                    'delivered_quantity' => 0,
                    'discount' => 0,
                    'discount_type' => 1,
                    'sale_price' => (float) ($product->sale_price ?? 0),
                    'fob_price' => (float) ($product->fob_price ?? 0),
                    'purchase_price' => (float) ($product->purchase_price ?? 0),
                    'comment' => '',
                    'comments' => '',
                    'id_kor' => 0,
                    'created' => $now,
                    'company_id' => $companyId,
                ]);
            }

            return $order->fresh(['statusOrder']);
        });
    }

    public function nextLegacyOrderSequence(int $companyId): int
    {
        $prefix = 'INV';
        $result = DB::table('orders')
            ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(order_number, ?) AS UNSIGNED)), 0) as orderNumber', [strlen($prefix) + 1])
            ->where('company_id', $companyId)
            ->where('order_number', 'LIKE', $prefix.'%')
            ->whereRaw('LENGTH(order_number) >= ?', [strlen($prefix) + 1])
            ->first();

        return ($result && $result->orderNumber !== null ? (int) $result->orderNumber : 0) + 1;
    }

    public function generateLegacyOrderNumber(int $companyId): string
    {
        return 'INV'.sprintf('%06d', $this->nextLegacyOrderSequence($companyId));
    }

    /**
     * @return array<int, object>
     */
    public function orderDetails(int $companyId, int $orderId): array
    {
        $order = Order::query()
            ->where('company_id', $companyId)
            ->where('order_id', $orderId)
            ->where('tipo_d', 'I')
            ->first();

        if ($order === null) {
            return [];
        }

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->where('category_status', 1)
            ->get(['cat_id', 'category_name']);

        $catMap = [];
        foreach ($categories as $cat) {
            $catMap[(int) $cat->cat_id] = (string) $cat->category_name;
        }

        $details = OrderDetail::query()
            ->where('order_id', $orderId)
            ->with(['product' => static function ($q) {
                $cols = ['pro_id', 'sku', 'name', 'category_id', 'stock'];
                if (Schema::hasColumn('products', 'imgURL')) {
                    $cols[] = 'imgURL';
                }
                if (Schema::hasColumn('products', 'default_image_id')) {
                    $cols[] = 'default_image_id';
                }
                $q->select($cols);
            }])
            ->orderBy('detail_id')
            ->get();

        $productIds = [];
        foreach ($details as $detail) {
            if ($detail->product !== null) {
                $productIds[] = (int) $detail->product->pro_id;
            }
        }
        $imageMetaByProduct = $this->primaryImageMetaByProductIds($productIds);

        $rows = [];
        foreach ($details as $detail) {
            $product = $detail->product;
            if ($product === null) {
                continue;
            }

            $proId = (int) $product->pro_id;
            $catNames = $this->resolveCategoryNames((string) ($product->category_id ?? ''), $catMap);
            $meta = $imageMetaByProduct[$proId] ?? ['uuid' => '', 'name' => ''];

            $uuid = trim((string) ($meta['uuid'] ?? ''));
            if ($uuid === '' && Schema::hasColumn('products', 'default_image_id')) {
                $uuid = trim((string) ($product->default_image_id ?? ''));
            }

            $rows[] = (object) [
                'detail_id' => (int) $detail->detail_id,
                'pro_id' => $proId,
                'sku' => (string) ($product->sku ?? ''),
                'name' => (string) ($product->name ?? ''),
                'category_id' => (string) ($product->category_id ?? ''),
                'category_name' => $catNames,
                'stock_ERP' => (float) ($product->stock ?? 0),
                'qty' => (float) ($detail->qty ?? 0),
                'delivered_pack' => (float) ($detail->delivered_pack ?? -1),
                'comments' => (string) ($detail->comments ?? ''),
                'legacy_imgURL' => trim((string) ($product->imgURL ?? '')),
                'image_name' => (string) ($meta['name'] ?? ''),
                'image_uuid' => $uuid,
            ];
        }

        return $rows;
    }

    /**
     * UUID + nombre de imagen principal por producto (default_image_id o primer product_images).
     *
     * @param  array<int, int>  $productIds
     * @return array<int, array{uuid: string, name: string}>
     */
    private function primaryImageMetaByProductIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === [] || ! Schema::hasTable('images')) {
            return [];
        }

        $map = [];

        if (Schema::hasColumn('products', 'default_image_id')) {
            $defaults = DB::table('products as p')
                ->leftJoin('images as img', 'img.uuid', '=', 'p.default_image_id')
                ->whereIn('p.pro_id', $productIds)
                ->whereNotNull('p.default_image_id')
                ->select('p.pro_id', 'p.default_image_id as uuid', 'img.name')
                ->get();

            foreach ($defaults as $row) {
                $pid = (int) $row->pro_id;
                $uuid = trim((string) ($row->uuid ?? ''));
                if ($uuid === '') {
                    continue;
                }
                $map[$pid] = [
                    'uuid' => $uuid,
                    'name' => trim((string) ($row->name ?? '')),
                ];
            }
        }

        $missing = array_values(array_filter($productIds, static fn (int $id) => ! isset($map[$id])));
        if ($missing === [] || ! Schema::hasTable('product_images')) {
            return $map;
        }

        $piQuery = DB::table('product_images as pimg')
            ->join('images as img', 'img.uuid', '=', 'pimg.image_id')
            ->whereIn('pimg.product_id', $missing)
            ->orderBy('pimg.img_order')
            ->orderBy('pimg.img_id')
            ->select('pimg.product_id', 'pimg.image_id as uuid', 'img.name');

        if (Schema::hasColumn('product_images', 'deleted_at')) {
            $piQuery->whereNull('pimg.deleted_at');
        }

        foreach ($piQuery->get() as $row) {
            $pid = (int) $row->product_id;
            if (isset($map[$pid])) {
                continue;
            }
            $uuid = trim((string) ($row->uuid ?? ''));
            if ($uuid === '') {
                continue;
            }
            $map[$pid] = [
                'uuid' => $uuid,
                'name' => trim((string) ($row->name ?? '')),
            ];
        }

        return $map;
    }

    /**
     * @param array<int, string> $catMap
     */
    private function resolveCategoryNames(string $categoryCsv, array $catMap): string
    {
        $names = [];
        foreach (explode(',', $categoryCsv) as $piece) {
            $id = (int) trim($piece);
            if ($id > 0 && isset($catMap[$id])) {
                $names[] = $catMap[$id];
            }
        }

        return implode(' | ', $names);
    }

    /**
     * @param array<int, int|string> $orderIds
     * @return array<int, object>
     */
    public function exportRows(int $companyId, array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds)));
        if ($orderIds === []) {
            return [];
        }

        $hasLegacyWu = Schema::hasTable('warehouse_users')
            && Schema::hasColumn('warehouse_users', 'first_name');

        $query = OrderDetail::withoutGlobalScopes()
            ->from('order_details as od')
            ->join('orders as ord', 'ord.order_id', '=', 'od.order_id')
            ->join('products as p', 'p.pro_id', '=', 'od.product_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'ord.customer_id')
            ->leftJoin('status_orders as so', 'so.id_status', '=', 'ord.status_id')
            ->where('ord.company_id', $companyId)
            ->whereIn('ord.order_id', $orderIds)
            ->where('ord.tipo_d', 'I')
            ->whereNull('od.deleted_at')
            ->select([
                'ord.order_number',
                'ord.order_id',
                'ord.warehouse_user_id',
                'od.qty',
                'od.comments',
                'od.created',
                'p.sku',
                'p.stock as stock_erp',
                'p.name',
                'so.cod_status as order_status',
            ])
            ->orderByDesc('ord.order_id')
            ->orderBy('od.detail_id');

        $rows = $query->get();
        $userNames = $this->warehouseUserNames($rows->pluck('warehouse_user_id')->unique()->filter()->all(), $hasLegacyWu);

        $out = [];
        foreach ($rows as $row) {
            $wid = (int) ($row->warehouse_user_id ?? 0);
            $out[] = (object) [
                'order_number' => (string) ($row->order_number ?? ''),
                'order_id' => (int) ($row->order_id ?? 0),
                'order_status' => (int) ($row->order_status ?? 0),
                'sku' => (string) ($row->sku ?? ''),
                'stock_erp' => (float) ($row->stock_erp ?? 0),
                'name' => (string) ($row->name ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'created' => $row->created ? (string) $row->created : '',
                'comments' => (string) ($row->comments ?? ''),
                'deposito_asignado' => $userNames[$wid] ?? '',
            ];
        }

        return $out;
    }

    /**
     * @param array<int, int|string|null> $userIds
     * @return array<int, string>
     */
    private function warehouseUserNames(array $userIds, bool $hasLegacyWu): array
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds)));
        if ($userIds === []) {
            return [];
        }

        if ($hasLegacyWu) {
            $rows = DB::table('warehouse_users')
                ->whereIn('user_id', $userIds)
                ->get(['user_id', 'first_name', 'last_name']);
            $map = [];
            foreach ($rows as $r) {
                $map[(int) $r->user_id] = trim((string) ($r->first_name ?? '').' '.(string) ($r->last_name ?? ''));
            }

            return $map;
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(static function (User $u) {
                $label = trim((string) ($u->name ?? ''));
                if ($label === '') {
                    $label = trim((string) ($u->email ?? ''));
                }

                return [(int) $u->id => $label];
            })
            ->all();
    }
}
