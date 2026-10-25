<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductInventoryMvcService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginatedList(int $companyId, array $filters = [], int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = max(10, min(100, $perPage));
        $page = max(1, $page);

        $query = $this->buildListQuery($companyId, $filters);
        $sortBy = (string) ($filters['sort_by'] ?? 'sku');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSort = [
            'sku' => 'p.sku',
            'name' => 'p.name',
            'sale_price' => 'p.sale_price',
            'purchase_price' => 'p.purchase_price',
            'inventory_qty' => 'inventory_qty',
            'stock' => 'p.stock',
        ];
        $sortColumn = $allowedSort[$sortBy] ?? 'p.sku';

        return $query
            ->orderBy($sortColumn, $sortDir)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    public function exportRows(int $companyId, array $filters = []): Collection
    {
        $query = $this->buildListQuery($companyId, $filters);

        return $query
            ->orderBy('p.sku')
            ->get();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildListQuery(int $companyId, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $stockFilter = (string) ($filters['stock_filter'] ?? 'all');
        $categoryIds = $this->normalizeCategoryIds($filters['category_ids'] ?? []);

        $query = Product::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('products as p')
            ->leftJoin('inventory as i', static function ($join) {
                $join->on('i.company_id', '=', 'p.company_id')
                    ->on('i.product_id', '=', 'p.pro_id');
            })
            ->where('p.company_id', $companyId);

        if (Schema::hasColumn('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        if ($search !== '') {
            $query->where(static function ($q) use ($search) {
                $q->where('p.sku', 'like', '%'.$search.'%')
                    ->orWhere('p.name', 'like', '%'.$search.'%');
            });
        }

        if ($stockFilter === 'with_stock') {
            $query->whereRaw('COALESCE(i.qty, p.stock, 0) > 0');
        } elseif ($stockFilter === 'without_stock') {
            $query->whereRaw('COALESCE(i.qty, p.stock, 0) <= 0');
        }

        if ($categoryIds !== []) {
            $query->where(static function ($q) use ($categoryIds) {
                $q->whereExists(static function ($sub) use ($categoryIds) {
                    $sub->from('category_product as cp')
                        ->whereColumn('cp.product_id', 'p.pro_id')
                        ->whereIn('cp.category_id', $categoryIds);
                    if (Schema::hasColumn('category_product', 'deleted_at')) {
                        $sub->whereNull('cp.deleted_at');
                    }
                });

                if (Schema::hasColumn('products', 'category_id')) {
                    $q->orWhere(static function ($legacy) use ($categoryIds) {
                        foreach ($categoryIds as $catId) {
                            $legacy->orWhere('p.category_id', 'like', '%'.$catId.'%');
                        }
                    });
                }
            });
        }

        $categoriesSub = "SELECT GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ')
            FROM category_product cp
            INNER JOIN categories c ON c.cat_id = cp.category_id
            WHERE cp.product_id = p.pro_id";

        if (Schema::hasColumn('category_product', 'deleted_at')) {
            $categoriesSub .= ' AND cp.deleted_at IS NULL';
        }

        $piDeleted = Schema::hasColumn('product_images', 'deleted_at') ? ' AND pi.deleted_at IS NULL' : '';
        $firstImageSub = '(SELECT pi.image_id FROM product_images pi WHERE pi.product_id = p.pro_id'.$piDeleted
            .' ORDER BY COALESCE(pi.img_order, 999999) ASC, pi.img_id ASC LIMIT 1)';

        if (Schema::hasColumn('products', 'unit_id')) {
            $query->leftJoin('units as u', 'u.unit_id', '=', 'p.unit_id');
        }

        $select = [
            'p.pro_id',
            'p.product_id',
            'p.sku',
            'p.name',
            'p.sale_price',
            'p.purchase_price',
            'p.stock',
            'p.status',
            DB::raw('COALESCE(i.qty, p.stock, 0) as inventory_qty'),
            'i.modify_date as inventory_updated_at',
            DB::raw('('.$categoriesSub.') as categories'),
            DB::raw('COALESCE(p.default_image_id, '.$firstImageSub.') as product_image_uuid'),
        ];

        if (Schema::hasColumn('products', 'default_image_id')) {
            $select[] = 'p.default_image_id';
        }
        if (Schema::hasColumn('products', 'unit_id')) {
            $select[] = 'p.unit_id';
            $select[] = 'u.unit as unit_name';
            $select[] = 'u.description as unit_description';
        }
        if (Schema::hasColumn('products', 'unit_type')) {
            $select[] = 'p.unit_type';
        }

        $imageExtSub = '(SELECT LOWER(iimg.extension) FROM images iimg WHERE iimg.uuid = COALESCE(p.default_image_id, '
            .$firstImageSub.') LIMIT 1)';
        if (Schema::hasTable('images')) {
            $select[] = DB::raw($imageExtSub.' as product_image_ext');
        }

        return $query->select($select);
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function normalizeCategoryIds($raw): array
    {
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array{
     *     product: array<string, mixed>|null,
     *     movements: list<array<string, mixed>>,
     *     current_balance: float
     * }
     */
    public function productMovements(int $companyId, int $proId): array
    {
        $product = Product::query()
            ->where('company_id', $companyId)
            ->where('pro_id', $proId)
            ->first(['pro_id', 'product_id', 'sku', 'name', 'sale_price', 'purchase_price', 'stock']);

        if ($product === null) {
            return [
                'product' => null,
                'movements' => [],
                'current_balance' => 0.0,
            ];
        }

        $rows = DB::table('order_details as od')
            ->join('orders as o', 'o.order_id', '=', 'od.order_id')
            ->leftJoin('status_orders as so', 'so.id_status', '=', 'o.status_id')
            ->where('o.company_id', $companyId)
            ->where('od.product_id', $proId)
            ->when(Schema::hasColumn('order_details', 'deleted_at'), static function ($query) {
                $query->whereNull('od.deleted_at');
            })
            ->orderBy('od.created')
            ->orderBy('od.detail_id')
            ->get([
                'od.detail_id',
                'od.qty',
                'od.sale_price',
                'od.purchase_price',
                'od.comments',
                'od.created',
                'o.order_id',
                'o.order_number',
                'o.tipo_d',
                'o.created as order_created',
                'so.cod_status as status_code',
            ]);

        $balance = 0.0;
        $movements = [];

        foreach ($rows as $row) {
            $qty = (float) ($row->qty ?? 0);
            $tipoD = (string) ($row->tipo_d ?? '');
            $effect = $this->stockEffect($tipoD, $qty);

            if ($effect === 0.0 && abs($qty + 1) < 0.0001 && $tipoD === 'I') {
                continue;
            }

            $balance += $effect;

            $movements[] = [
                'detail_id' => (int) $row->detail_id,
                'order_id' => (int) $row->order_id,
                'order_number' => (string) ($row->order_number ?? ''),
                'tipo_d' => $tipoD,
                'movement_date' => $row->created ? (string) $row->created : (string) ($row->order_created ?? ''),
                'qty' => $qty,
                'qty_in' => $effect > 0 ? $effect : 0.0,
                'qty_out' => $effect < 0 ? abs($effect) : 0.0,
                'balance' => $balance,
                'sale_price' => (float) ($row->sale_price ?? 0),
                'purchase_price' => (float) ($row->purchase_price ?? 0),
                'comments' => (string) ($row->comments ?? ''),
                'status_code' => $row->status_code !== null ? (int) $row->status_code : null,
            ];
        }

        $inventoryQty = (float) DB::table('inventory')
            ->where('company_id', $companyId)
            ->where('product_id', $product->pro_id)
            ->value('qty');

        if ($inventoryQty === 0.0) {
            $inventoryQty = (float) ($product->stock ?? 0);
        }

        return [
            'product' => [
                'pro_id' => (int) $product->pro_id,
                'product_id' => (int) ($product->product_id ?? 0),
                'sku' => (string) ($product->sku ?? ''),
                'name' => (string) ($product->name ?? ''),
                'sale_price' => (float) ($product->sale_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),
                'inventory_qty' => $inventoryQty,
            ],
            'movements' => array_reverse($movements),
            'current_balance' => $balance,
        ];
    }

    private function stockEffect(string $tipoD, float $qty): float
    {
        if (abs($qty) < 0.0001) {
            return 0.0;
        }

        return -1.0 * $qty;
    }
}
