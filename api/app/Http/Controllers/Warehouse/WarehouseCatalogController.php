<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Image;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Catálogo paginado exclusivo de FullVendor Warehouse.
 * No modifica product/all, customer/all ni category/all (salesman / otros).
 */
#[OA\Tag(
    name: 'WarehouseCatalog',
    description: 'Catálogo paginado para sync total de FullVendor Warehouse'
)]
class WarehouseCatalogController extends Controller
{
    private const DEFAULT_PER_PAGE = 200;

    private const MAX_PER_PAGE = 500;

    #[OA\Get(
        path: 'api/v3/warehouse/catalog/products',
        summary: 'Productos paginados (lite) para sync warehouse — solo enabled (status=1)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseCatalog']
    )]
    public function products(Request $request): JsonResponse
    {
        return $this->paginateProducts($request, onlyEnabled: true);
    }

    #[OA\Get(
        path: 'api/v3/warehouse/catalog/products-all',
        summary: 'Productos warehouse: TODOS (enabled + disabled), sin filtrar por status',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseCatalog']
    )]
    public function productsAll(Request $request): JsonResponse
    {
        return $this->paginateProducts($request, onlyEnabled: false);
    }

    #[OA\Get(
        path: 'api/v3/warehouse/catalog/customers',
        summary: 'Clientes paginados para sync warehouse (nuevo; no toca customer/all)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseCatalog']
    )]
    public function customers(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return $this->companyRequired();
        }

        [$page, $perPage] = $this->pagination($request);
        set_time_limit(0);

        // Sin with('sellers'): evita hidratar Vendor por cliente (muy lento en sync).
        $paginator = Customer::query()
            ->where('company_id', $companyId)
            ->orderBy('customer_id')
            ->paginate($perPage, ['*'], 'page', $page);

        $customerIds = collect($paginator->items())->pluck('customer_id')->filter()->all();
        $sellersByCustomer = [];
        if ($customerIds !== []) {
            $pivotRows = DB::table('customer_seller')
                ->whereIn('customer_id', $customerIds)
                ->whereNull('deleted_at')
                ->get(['customer_id', 'seller_id']);
            foreach ($pivotRows as $pivot) {
                $cid = (int) $pivot->customer_id;
                $sellersByCustomer[$cid][] = ['id' => (int) $pivot->seller_id];
            }
        }

        $data = collect($paginator->items())->map(function (Customer $customer) use ($sellersByCustomer) {
            $row = $customer->toArray();
            $row['sellers'] = $sellersByCustomer[(int) $customer->customer_id] ?? [];

            return $row;
        })->values()->all();

        return response()->json($this->pagePayload($paginator, $data));
    }

    #[OA\Get(
        path: 'api/v3/warehouse/catalog/customers-with-orders',
        summary: 'Clientes con órdenes en el último año (para notas de crédito warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseCatalog']
    )]
    public function customersWithOrders(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return $this->companyRequired();
        }

        [$page, $perPage] = $this->pagination($request);
        set_time_limit(0);

        $since = Carbon::now()->subYear()->startOfDay();

        // Agregado por cliente: 1 fila/cliente con última orden y conteo.
        $paginator = DB::table('orders')
            ->select([
                'customer_id',
                DB::raw('MAX(`created`) as last_order_at'),
                DB::raw('COUNT(*) as orders_count'),
            ])
            ->where('company_id', $companyId)
            ->where('customer_id', '>', 0)
            ->where('created', '>=', $since)
            ->groupBy('customer_id')
            ->orderByDesc(DB::raw('MAX(`created`)'))
            ->paginate($perPage, ['*'], 'page', $page);

        $ids = collect($paginator->items())
            ->pluck('customer_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        $customersById = [];
        if ($ids !== []) {
            $customersById = Customer::query()
                ->where('company_id', $companyId)
                ->whereIn('customer_id', $ids)
                ->get()
                ->keyBy('customer_id');
        }

        $data = [];
        foreach ($paginator->items() as $row) {
            $customerId = (int) $row->customer_id;
            $customer = $customersById[$customerId] ?? null;
            if ($customer === null) {
                continue;
            }

            $data[] = [
                'customer_id' => $customerId,
                'company_id' => (int) ($customer->company_id ?? $companyId),
                'name' => $customer->name,
                'business_name' => $customer->business_name,
                'tax_id' => $customer->tax_id,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'cell_phone' => $customer->cell_phone,
                'commercial_address' => $customer->commercial_address,
                'commercial_city' => $customer->commercial_city,
                'commercial_state' => $customer->commercial_state,
                'commercial_country' => $customer->commercial_country,
                'commercial_zip_code' => $customer->commercial_zip_code,
                'commercial_zone' => $customer->commercial_zone,
                'last_order_at' => $row->last_order_at,
                'orders_count' => (int) $row->orders_count,
            ];
        }

        $payload = $this->pagePayload($paginator, $data);
        $payload['success'] = true;
        $payload['company_id'] = $companyId;
        $payload['since'] = $since->toIso8601String();

        return response()->json($payload);
    }

    #[OA\Get(
        path: 'api/v3/warehouse/catalog/categories',
        summary: 'Categorías paginadas para sync warehouse (orden por cat_id)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseCatalog']
    )]
    public function categories(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return $this->companyRequired();
        }

        [$page, $perPage] = $this->pagination($request);
        set_time_limit(0);

        $paginator = Category::query()
            ->where('company_id', $companyId)
            ->with(['imageRecord' => static function ($q) {
                $q->select('uuid', 'extension', 'disk', 'path', 'name');
            }])
            ->orderBy('cat_id')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->map(fn (Category $category) => $this->mapCategoryRow($category))
            ->values()
            ->all();

        return response()->json($this->pagePayload($paginator, $data));
    }

    #[OA\Get(
        path: 'api/v3/warehouse/catalog/categories-ordered',
        summary: 'Categorías warehouse ordenadas según catálogo default (order_categories / catalog_category.order)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseCatalog']
    )]
    public function categoriesOrdered(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return $this->companyRequired();
        }

        [$page, $perPage] = $this->pagination($request);
        set_time_limit(0);

        $catalogId = (int) (Catalog::query()
            ->where('company_id', $companyId)
            ->where('default_catalog', 1)
            ->value('catalog_id') ?? 0);

        $query = Category::query()
            ->where('categories.company_id', $companyId)
            ->with(['imageRecord' => static function ($q) {
                $q->select('uuid', 'extension', 'disk', 'path', 'name');
            }]);

        if ($catalogId > 0) {
            // Orden del catálogo FullVendor (default_catalog=1) vía order_categories.
            $query
                ->leftJoin('order_categories as oc', function ($join) use ($companyId, $catalogId) {
                    $join->on('oc.category_id', '=', 'categories.cat_id')
                        ->where('oc.company_id', '=', $companyId)
                        ->where('oc.catalog_id', '=', $catalogId)
                        ->whereNull('oc.deleted_at');
                })
                ->select('categories.*')
                ->addSelect(DB::raw('oc.`order` as catalog_order'))
                ->orderByRaw('CASE WHEN COALESCE(categories.category_default, 0) = 1 THEN 1 ELSE 0 END')
                ->orderByRaw('CASE WHEN oc.`order` IS NULL THEN 1 ELSE 0 END')
                ->orderBy('oc.order')
                ->orderBy('categories.cat_id');
        } else {
            $query
                ->addSelect(DB::raw('NULL as catalog_order'))
                ->orderByRaw('CASE WHEN COALESCE(category_default, 0) = 1 THEN 1 ELSE 0 END')
                ->orderBy('cat_id');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->map(function (Category $category) {
                $row = $this->mapCategoryRow($category);
                $row['catalog_order'] = isset($category->catalog_order)
                    ? (int) $category->catalog_order
                    : null;

                return $row;
            })
            ->values()
            ->all();

        return response()->json($this->pagePayload($paginator, $data));
    }

    private function paginateProducts(Request $request, bool $onlyEnabled): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return $this->companyRequired();
        }

        [$page, $perPage] = $this->pagination($request);
        set_time_limit(0);

        $query = Product::query()
            ->without('unit')
            ->where('company_id', $companyId);

        if ($onlyEnabled) {
            $query->where('products.status', 1);
        }

        $paginator = $query
            ->addSelect([
                'unit_value' => DB::table('units')
                    ->select('unit')
                    ->whereColumn('units.unit_id', 'products.unit_id')
                    ->limit(1),
            ])
            ->with([
                'default_image' => static function ($q) {
                    $q->select('uuid', 'name', 'path', 'company_id', 'disk', 'extension', 'mime_type');
                },
                'categories' => static function ($q) {
                    $q->select('categories.cat_id', 'categories.category_name');
                },
            ])
            ->orderByDesc('modified_on')
            ->orderBy('pro_id')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->map(fn (Product $product) => $this->mapProductRow($product))
            ->values()
            ->all();

        return response()->json($this->pagePayload($paginator, $data));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProductRow(Product $product): array
    {
        $categories = $product->categories ?? collect();
        $categoryNames = $categories->pluck('category_name')->filter()->implode(',');
        $categoryIds = $categories->pluck('cat_id')->filter()->implode(',');

        $imageUuid = $product->default_image?->uuid;
        $imgExt = 'webp';
        if ($product->default_image) {
            $e = strtolower((string) ($product->default_image->extension ?? 'webp'));
            $imgExt = in_array($e, ['webp', 'png', 'jpg', 'jpeg', 'gif'], true) ? $e : 'webp';
            if (! Image::isOnActiveStorage($product->default_image)) {
                $imageUuid = null;
            }
        }

        $img = $imageUuid ? ('/images/'.$imageUuid.'.'.$imgExt) : null;

        return [
            'pro_id' => (int) $product->pro_id,
            'product_id' => (int) $product->pro_id,
            'language_id' => $product->language_id !== null ? (int) $product->language_id : null,
            'company_id' => $product->company_id !== null ? (int) $product->company_id : null,
            'warehouse_user_id' => $product->warehouse_user_id !== null ? (int) $product->warehouse_user_id : null,
            'sku' => $product->sku,
            'name' => $product->name,
            'sale_price' => $product->sale_price,
            'purchase_price' => $product->purchase_price ?? null,
            'category_id' => $categoryNames !== '' ? $categoryNames : null,
            'barcode' => $product->barcode ?? null,
            'barcode_images' => $product->barcode_images ?? null,
            'tags' => $product->tags ?? null,
            'descriptions' => $product->descriptions ?? null,
            'unit_type' => $product->unit_value ?? null,
            'unit' => $product->unit_value ?? null,
            'stock' => (float) ($product->stock ?? 0),
            'minimum_stock' => (float) ($product->minimum_stock ?? 0),
            'notify_minimum_stock' => (int) ($product->notify_minimum_stock ?? 0),
            'currency_type' => $product->currency_type ?? null,
            'fob_price' => $product->fob_price ?? null,
            'locations' => $product->locations ?? null,
            'created_by' => $product->created_by ?? null,
            'created_on' => optional($product->created_on)->toIso8601String(),
            'modified_on' => optional($product->modified_on)->toIso8601String(),
            'status' => (int) ($product->status ?? 0),
            'Img' => $img,
            'imgURL' => $img,
            'simgURL' => $img,
            'id_kor' => $product->id_kor ?? null,
            // cat_id numérico (primer cat); cats_id lista "1,2,3" para SQLite/app.
            // isNotEmpty() es método; ->isNotEmpty (propiedad) rompe el sync warehouse.
            'cat_id' => $categories->isNotEmpty() ? (int) $categories->first()->cat_id : null,
            'cats_id' => $categoryIds !== '' ? $categoryIds : null,
            'sell_with_inventory' => $product->sell_with_inventory ?? null,
            'image_updated' => $product->image_updated ?? null,
            'price_reduced' => (float) ($product->price_reduced ?? 0),
            'sem_red_hasta' => $product->sem_red_hasta ?? null,
            'sem_amarillo_desde' => $product->sem_amarillo_desde ?? null,
            'sem_amarillo_hasta' => $product->sem_amarillo_hasta ?? null,
            'sem_verde_desde' => $product->sem_verde_desde ?? null,
            'sem_verde_hasta' => $product->sem_verde_hasta ?? null,
            'updated_at' => optional($product->updated_at)->toIso8601String(),
            'deleted_at' => optional($product->deleted_at)->toIso8601String(),
            'has_photo' => $img !== null,
            'has_image' => $img !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCategoryRow(Category $category): array
    {
        $row = $category->toArray();
        unset($row['catalog_order']);

        $imageUrl = null;
        $image = $category->imageRecord;
        if ($image && $image->uuid) {
            $ext = strtolower((string) ($image->extension ?? 'webp'));
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'webp';
            }
            if (Image::isOnActiveStorage($image)) {
                $imageUrl = '/images/'.$image->uuid.'.'.$ext;
            }
        }
        $row['imageurl'] = $imageUrl;
        $row['image_url'] = $imageUrl;
        $row['has_photo'] = $imageUrl !== null;
        $row['has_image'] = $imageUrl !== null;

        return $row;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function pagination(Request $request): array
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);
        if ($perPage < 1) {
            $perPage = self::DEFAULT_PER_PAGE;
        }
        if ($perPage > self::MAX_PER_PAGE) {
            $perPage = self::MAX_PER_PAGE;
        }

        return [$page, $perPage];
    }

    private function resolveCompanyId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $companyId = $request->input('company_id') ?? $request->input('company');
        if (! $companyId) {
            $userCompany = $user->companiesForRole('manager')->first()
                ?? $user->companies()->first();
            $companyId = $userCompany?->company_id;
        }

        $companyId = (int) $companyId;

        return $companyId > 0 ? $companyId : null;
    }

    private function companyRequired(): JsonResponse
    {
        return response()->json([
            'message' => 'No se pudo determinar la compañía. Asegúrese de estar asociado a una compañía.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @param  list<array<string, mixed>>  $data
     * @return array<string, mixed>
     */
    private function pagePayload($paginator, array $data): array
    {
        return [
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
