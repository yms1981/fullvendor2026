<?php

namespace App\Services;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\User;
use App\Support\CurrencySchemaHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseOrderMvcService
{
    /** @var array<int, int> */
    private const WAREHOUSE_STATUS_CODES = [9, 11, 12];

    /**
     * @return array<int, object>
     */
    public function warehouseStatusList(int $langId): array
    {
        $nameCol = $langId === 2 ? 'name_status_spanish' : 'name_status_english';

        return StatusOrder::query()
            ->whereIn('cod_status', self::WAREHOUSE_STATUS_CODES)
            ->orderBy($nameCol)
            ->get(['cod_status', 'color', 'disabled', 'can_show_data_asigned', $nameCol])
            ->map(static function ($row) use ($nameCol) {
                return (object) [
                    'name_status' => (string) ($row->{$nameCol} ?? ''),
                    'cod_status' => (int) $row->cod_status,
                    'color' => (string) ($row->color ?? ''),
                    'disabled' => (int) ($row->disabled ?? 0),
                    'can_show_data_asigned' => (int) ($row->can_show_data_asigned ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    public function findCompanyOrder(int $companyId, int $orderId): ?Order
    {
        return Order::query()
            ->where('company_id', $companyId)
            ->where('order_id', $orderId)
            ->with([
                'customer.customer_group:group_id,name,percentage_on_price',
                'statusOrder:id_status,cod_status',
                'seller.user:id,name',
            ])
            ->first();
    }

    public function markViewedIfPlaced(Order $order): void
    {
        $cod = $order->statusOrder !== null
            ? (int) $order->statusOrder->cod_status
            : (int) ($order->order_status ?? -1);

        if ($cod !== OrderStatusCodes::PLACED->value) {
            return;
        }

        $readId = StatusOrder::getIdByCode(OrderStatusCodes::READ);
        if ($readId === false) {
            return;
        }

        $updates = [];
        if (Schema::hasColumn('orders', 'status_id')) {
            $updates['status_id'] = (int) $readId;
        }
        if (Schema::hasColumn('orders', 'order_status')) {
            $updates['order_status'] = OrderStatusCodes::READ->value;
        }
        if ($updates !== []) {
            $order->update($updates);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function orderShowPayload(int $companyId, int $orderId): ?array
    {
        $order = $this->findCompanyOrder($companyId, $orderId);
        if ($order === null) {
            return null;
        }

        $this->markViewedIfPlaced($order);
        $order->refresh();
        $order->load(['customer.customer_group', 'statusOrder', 'seller.user', 'notes']);

        $codStatus = $order->statusOrder !== null
            ? (int) $order->statusOrder->cod_status
            : (int) ($order->order_status ?? 0);

        $whUserId = (int) ($order->warehouse_user_id ?? $order->warehouse_id ?? 0);
        $notesByType = $this->notesByType($order);

        $orderData = (object) [
            'order_id' => (int) $order->order_id,
            'order_number' => (string) ($order->order_number ?? ''),
            'company_id' => (int) $order->company_id,
            'customer_id' => (int) ($order->customer_id ?? 0),
            'user_id' => (int) ($order->user_id ?? 0),
            'warehouse_user_id' => $whUserId > 0 ? $whUserId : null,
            'warehouse_id' => $order->warehouse_id ?? null,
            'order_status' => $codStatus,
            'source' => (string) ($order->source ?? ''),
            'order_comments' => $notesByType[OrderNoteTypes::Order->value]
                ?? (string) ($order->order_comments ?? ''),
            'delivery_notes' => $notesByType[OrderNoteTypes::Delivery->value]
                ?? $notesByType[OrderNoteTypes::Shipping->value]
                ?? (string) ($order->delivery_notes ?? ''),
            'discount' => (float) ($order->discount ?? 0),
            'discount_type' => (int) ($order->discount_type ?? 0),
            'approved_by' => $order->approved_by ?? null,
            'created' => $order->created ? $order->created->format('Y-m-d H:i:s') : null,
            'updated' => $order->updated ? $order->updated->format('Y-m-d H:i:s') : null,
        ];

        return [
            'order' => $orderData,
            'customer' => $order->customer,
            'seller' => $this->resolveSellerViewObject($order),
            'wuser' => $this->resolveWarehouseUserViewObject($companyId, $whUserId),
            'order_status_cod' => $codStatus,
        ];
    }

    /**
     * @return array<int, object>
     */
    public function orderDetailRows(int $companyId, int $orderId): array
    {
        $order = Order::query()
            ->where('company_id', $companyId)
            ->where('order_id', $orderId)
            ->with('customer.customer_group:group_id,name,percentage_on_price')
            ->first();

        if ($order === null) {
            return [];
        }

        $groupName = (string) ($order->customer?->customer_group?->name ?? '');
        $groupPct = $order->customer?->customer_group?->percentage_on_price ?? 0;

        $unitFormats = DB::table('units')
            ->where('company_id', $companyId)
            ->pluck('formato', 'unit_id');

        $details = OrderDetail::query()
            ->where('order_id', $orderId)
            ->orderBy('detail_id')
            ->get();

        if ($details->isEmpty()) {
            return [];
        }

        $productIds = $details->pluck('product_id')->unique()->filter()->values()->all();
        $products = Product::query()
            ->where('company_id', $companyId)
            ->whereIn('pro_id', $productIds)
            ->get()
            ->keyBy('pro_id');

        $imageMap = $this->primaryImageMetaByProductIds($productIds);

        $rows = [];
        foreach ($details as $detail) {
            $product = $products->get($detail->product_id);
            if ($product === null) {
                continue;
            }

            $qty = (float) ($detail->qty ?? 0);
            $delivered = (float) ($detail->delivered_quantity ?? 0);
            $price = (float) ($detail->sale_price ?? 0);
            $proId = (int) $product->pro_id;
            $meta = $imageMap[$proId] ?? ['uuid' => '', 'name' => ''];
            $imageUuid = trim((string) ($meta['uuid'] ?? ''));
            if ($imageUuid === '' && Schema::hasColumn('products', 'default_image_id')) {
                $imageUuid = trim((string) ($product->default_image_id ?? ''));
            }
            $imageName = (string) ($meta['name'] ?? '');

            $rows[] = (object) array_merge($product->toArray(), [
                'detail_id' => (int) $detail->detail_id,
                'product_id' => $proId,
                'qty' => $qty,
                'delivered_quantity' => $delivered,
                'sale_price' => $price,
                'precio_cliente' => $price,
                'total_importe' => $qty * $price,
                'total_importe_asignado' => $delivered * $price,
                'comments' => (string) ($detail->comments ?? ''),
                'formato_unidad' => (string) ($unitFormats[$product->unit_id] ?? ''),
                'image_uuid' => $imageUuid,
                'imagen_principal' => $imageName,
                'imagen_por_defecto' => $imageName,
                'grupo_nombre' => $groupName,
                'percentage_on_price' => $groupPct,
            ]);
        }

        return $rows;
    }

    /**
     * UUID + nombre de imagen principal por producto.
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
     * @deprecated Usar primaryImageMetaByProductIds
     * @param array<int, int> $productIds
     * @return array<int, string>
     */
    private function primaryImagesByProductIds(array $productIds): array
    {
        $meta = $this->primaryImageMetaByProductIds($productIds);
        $map = [];
        foreach ($meta as $pid => $row) {
            if (! empty($row['name'])) {
                $map[$pid] = (string) $row['name'];
            }
        }

        return $map;
    }

    /**
     * @return array<int, object>
     */
    public function previouslyOrderedProducts(int $companyId, int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $completedId = StatusOrder::getIdByCode(OrderStatusCodes::COMPLETED);

        $query = Order::query()
            ->from('orders as o')
            ->join('order_details as od', 'od.order_id', '=', 'o.order_id')
            ->join('products as p', 'p.pro_id', '=', 'od.product_id')
            ->where('o.company_id', $companyId)
            ->where('o.customer_id', $customerId)
            ->select('o.order_id', 'od.product_id', 'p.name')
            ->groupBy('o.order_id', 'od.product_id', 'p.name');

        if ($completedId !== false && Schema::hasColumn('orders', 'status_id')) {
            $query->where('o.status_id', (int) $completedId);
        } elseif (Schema::hasColumn('orders', 'order_status')) {
            $query->where('o.order_status', OrderStatusCodes::COMPLETED->value);
        }

        return $query->get()->map(static fn ($row) => (object) [
            'order_id' => (int) $row->order_id,
            'product_id' => (int) $row->product_id,
            'name' => (string) $row->name,
        ])->all();
    }

    /**
     * @return array<int, object>
     */
    public function activeProductsList(int $companyId): array
    {
        // Solo columnas usadas por el select de "añadir línea" (evita OOM/timeouts).
        return Product::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['pro_id', 'name', 'sku'])
            ->map(static fn ($p) => (object) [
                'pro_id' => (int) $p->pro_id,
                'name' => (string) ($p->name ?? ''),
                'sku' => (string) ($p->sku ?? ''),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function notesByType(Order $order): array
    {
        $map = [];
        foreach ($order->notes ?? [] as $note) {
            $type = (string) ($note->type ?? '');
            if ($type === '' || isset($map[$type])) {
                continue;
            }
            $map[$type] = (string) ($note->note ?? '');
        }

        return $map;
    }

    private function resolveSellerViewObject(Order $order): ?object
    {
        $seller = $order->seller;
        if ($seller === null) {
            return null;
        }

        $user = $seller->user ?? null;
        if ($user !== null) {
            $name = trim((string) ($user->name ?? ''));
            $parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);

            return (object) [
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
                'name' => $name,
            ];
        }

        return (object) [
            'first_name' => (string) ($seller->first_name ?? ''),
            'last_name' => (string) ($seller->last_name ?? ''),
            'name' => trim(($seller->first_name ?? '').' '.($seller->last_name ?? '')),
        ];
    }

    private function resolveWarehouseUserViewObject(int $companyId, int $warehouseUserId): ?object
    {
        if ($warehouseUserId <= 0) {
            return null;
        }

        if (Schema::hasTable('warehouse_users') && Schema::hasColumn('warehouse_users', 'first_name')) {
            $legacy = DB::table('warehouse_users')
                ->where('company_id', $companyId)
                ->where('user_id', $warehouseUserId)
                ->first(['user_id', 'first_name', 'last_name', 'email']);
            if ($legacy !== null) {
                return (object) [
                    'user_id' => (int) $legacy->user_id,
                    'first_name' => (string) ($legacy->first_name ?? ''),
                    'last_name' => (string) ($legacy->last_name ?? ''),
                    'email' => (string) ($legacy->email ?? ''),
                ];
            }
        }

        $user = User::query()->where('id', $warehouseUserId)->first(['id', 'name', 'email']);
        if ($user === null) {
            return null;
        }

        $name = trim((string) ($user->name ?? ''));
        $parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);

        return (object) [
            'user_id' => (int) $user->id,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'email' => (string) ($user->email ?? ''),
        ];
    }

    public function companyCurrencySymbol(int $companyId): string
    {
        $company = Company::query()
            ->where('company_id', $companyId)
            ->with('currencyModel:'.CurrencySchemaHelper::primaryKeyColumn().',symbol')
            ->first(['company_id', 'currency']);

        return (string) ($company?->currencyModel?->symbol ?? '$');
    }
}
