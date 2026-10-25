<?php

namespace App\Services;

use App\Http\Controllers\ProductController;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SellerPortalCatalogService
{
    public function __construct(
        private readonly SellerPortalCustomerService $customerService,
        private readonly FullVendorCatalogService $fullVendorCatalog,
        private readonly OrderCatalogCartSessionService $cartSessions,
    ) {}

    /**
     * Equivalente a Helper::creat_categorydefault + Commonmodel::creat_fullvendorcatalog (MVC).
     */
    public function prepareCompanyCatalog(int $companyId): void
    {
        if ($companyId <= 0) {
            return;
        }

        DB::transaction(function () use ($companyId) {
            $defaultCategory = Category::query()->firstOrCreate(
                ['company_id' => $companyId, 'category_default' => 1],
                [
                    'category_name'       => 'Without Category',
                    'category_status'     => 1,
                    'category_created_at' => now(),
                ]
            );

            $catId = (int) $defaultCategory->cat_id;

            if (Schema::hasColumn('products', 'category_id')) {
                Product::query()
                    ->where('company_id', $companyId)
                    ->where(function ($query) {
                        $query->whereNull('category_id')
                            ->orWhere('category_id', 0)
                            ->orWhere('category_id', '');
                    })
                    ->update(['category_id' => $catId]);
            }

            $this->fullVendorCatalog->linkProductsWithoutCompanyCategory($companyId, $catId);
            $this->fullVendorCatalog->sync($companyId);
        });
    }

    /**
     * @return array{token_kor: string, catalog_id: int, customer_id: int}
     */
    public function createBrowseSession(Vendor $vendor, int $customerId): array
    {
        $customer = Customer::query()
            ->where('customer_id', $customerId)
            ->where('company_id', (int) $vendor->company_id)
            ->firstOrFail();

        if (! $this->customerService->customerAccessibleByVendor($vendor, $customer)) {
            throw new \RuntimeException('customer_forbidden');
        }

        $companyId = (int) $vendor->company_id;
        $this->prepareCompanyCatalog($companyId);

        return $this->cartSessions->ensure(
            $companyId,
            $customerId,
            (int) ($vendor->user_id ?? 0) ?: null
        );
    }

    public function resolveCatalogId(int $companyId, int $customerId): int
    {
        if ($companyId <= 0 || $customerId <= 0) {
            return 0;
        }

        if (Schema::hasTable('catalog_cart')) {
            $query = DB::table('catalog_cart')
                ->where('company_id', $companyId)
                ->where('customer_id', $customerId);

            if (Schema::hasColumn('catalog_cart', 'type_cart')) {
                $query->where('type_cart', 'to_customer');
            }
            if (Schema::hasColumn('catalog_cart', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if (Schema::hasColumn('catalog_cart', 'expired_date')) {
                $query->where('expired_date', '>', Carbon::now()->format('Y-m-d H:i:s'));
            }

            $row = $query->orderByDesc('cart_id')->first();
            if ($row !== null && (int) ($row->catalog_id ?? 0) > 0) {
                return (int) $row->catalog_id;
            }
        }

        if (! Schema::hasTable('catalog')) {
            return 0;
        }

        $catalogQuery = DB::table('catalog')->where('company_id', $companyId);
        if (Schema::hasColumn('catalog', 'default_catalog')) {
            $catalogQuery->orderByDesc('default_catalog');
        }
        $catalog = $catalogQuery->orderBy('catalog_id')->limit(1)->first();

        return $catalog !== null ? (int) ($catalog->catalog_id ?? 0) : 0;
    }

    /**
     * Productos del catálogo inline del portal (Flow B), mismo shape que GET /v2/product/all filtrado.
     *
     * @return list<array<string, mixed>>
     */
    public function productsForCustomer(Vendor $vendor, int $customerId): array
    {
        $customer = Customer::query()
            ->where('customer_id', $customerId)
            ->where('company_id', (int) $vendor->company_id)
            ->first();

        if ($customer === null || ! $this->customerService->customerAccessibleByVendor($vendor, $customer)) {
            return [];
        }

        $companyId = (int) $vendor->company_id;
        $catalogId = $this->resolveCatalogId($companyId, $customerId);
        if ($catalogId <= 0) {
            return [];
        }

        $request = Request::create('/api/v2/product/all', 'GET', ['company_id' => $companyId]);
        $request->setUserResolver(static fn () => auth()->user());

        /** @var ProductController $controller */
        $controller = app(ProductController::class);
        $response = $controller->all($request);
        $decoded = json_decode($response->getContent(), true);
        $rows = $decoded['data'] ?? [];
        if (is_array($rows) && isset($rows['data']) && is_array($rows['data'])) {
            $rows = $rows['data'];
        }
        if (! is_array($rows)) {
            return [];
        }

        return $this->filterProductsByCatalogMembership($rows, $companyId, $catalogId);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function filterProductsByCatalogMembership(array $rows, int $companyId, int $catalogId): array
    {
        if ($rows === [] || $companyId <= 0 || $catalogId <= 0) {
            return [];
        }

        $proIds = [];
        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }
            $proId = (int) ($item['pro_id'] ?? $item['product_id'] ?? 0);
            if ($proId > 0) {
                $proIds[] = $proId;
            }
        }
        $proIds = array_values(array_unique($proIds));
        if ($proIds === []) {
            return [];
        }

        $cpDeleted = Schema::hasColumn('catalog_product', 'deleted_at') ? ' AND cp.deleted_at IS NULL' : '';
        $ccDeleted = Schema::hasColumn('catalog_category', 'deleted_at') ? ' AND cc.deleted_at IS NULL' : '';

        $allowedRows = Product::query()
            ->select('products.pro_id')
            ->where('products.company_id', $companyId)
            ->whereIn('products.pro_id', $proIds)
            ->whereRaw(
                "(
                    EXISTS (SELECT 1 FROM catalog_product cp WHERE cp.catalog_id = ? AND cp.product_id = products.pro_id{$cpDeleted})
                    OR EXISTS (
                        SELECT 1 FROM catalog_category cc
                        INNER JOIN category_product cp2 ON cp2.category_id = cc.category_id AND cp2.product_id = products.pro_id
                        WHERE cc.catalog_id = ?{$ccDeleted}
                    )
                    OR EXISTS (
                        SELECT 1 FROM catalog c
                        WHERE c.catalog_id = ?
                        AND c.company_id = ?
                        AND c.product_name IS NOT NULL
                        AND c.product_name != ''
                        AND FIND_IN_SET(products.pro_id, c.product_name) > 0
                    )
                )",
                [$catalogId, $catalogId, $catalogId, $companyId]
            )
            ->get();

        $allowed = [];
        foreach ($allowedRows as $r) {
            $pid = (int) $r->pro_id;
            if ($pid > 0) {
                $allowed[$pid] = true;
            }
        }

        $out = [];
        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }
            $pid = (int) ($item['pro_id'] ?? $item['product_id'] ?? 0);
            if ($pid > 0 && isset($allowed[$pid])) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
