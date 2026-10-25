<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Http\Controllers\Company\CatalogController;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\Customer;
use App\Models\FormatNumeric;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

final class CatalogPublicCatalogService
{
    public function __construct()
    {
    }

    private function publicWebBaseUrl(): string
    {
        return rtrim((string) config('fullvendor.web_url', config('app.url', 'http://localhost')), '/');
    }

    private function catalogPublicImageUrl(string $imageRef, string $token): string
    {
        return $this->publicWebBaseUrl()
            . '/api/v2/images/catalog/'
            . $imageRef
            . '?token='
            . rawurlencode($token);
    }

    private function imageRefFromModel(?\App\Models\Image $image): ?string
    {
        if ($image === null) {
            return null;
        }

        $uuid = trim((string) ($image->uuid ?? ''));
        if ($uuid === '') {
            return null;
        }

        $ext = ltrim(strtolower((string) ($image->extension ?? 'png')), '.');
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (! in_array($ext, ['jpg', 'png', 'gif', 'webp', 'bmp', 'avif', 'tif', 'tiff', 'heic', 'heif', 'svg', 'ico'], true)) {
            $ext = 'png';
        }

        return $uuid . '.' . $ext;
    }

    private function primaryImageRefForProduct(Product $product): ?string
    {
        $refs = $this->allImageRefsForProduct($product);

        return $refs[0] ?? null;
    }

    /**
     * @return list<string>
     */
    private function allImageRefsForProduct(Product $product): array
    {
        if (! $product->relationLoaded('product_images')) {
            $product->load(['product_images.image']);
        }

        $refs = [];
        $productImages = $product->product_images
            ->sortBy(fn ($row) => (int) ($row->img_order ?? 999999))
            ->values();

        foreach ($productImages as $productImage) {
            $ref = $this->imageRefFromModel($productImage->image);
            if ($ref !== null) {
                $refs[] = $ref;
            }
        }

        return $refs;
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function products(stdClass $cart, string $token, Request $request): array
    {
        $catalogId = (int) ($cart->catalog_id ?? 0);
        $customerId = (int) ($cart->customer_id ?? 0);
        $companyId = (int) ($cart->company_id ?? 0);

        if ($catalogId < 1 || $customerId < 1) {
            return ['data' => [], 'sections' => [], 'meta' => ['total' => 0, 'page' => 1, 'per_page' => 28], 'grouped' => false];
        }

        $customer = Customer::query()->with('customer_group')->findOrFail($customerId);
        $grouped = $request->boolean('grouped');
        $this->mergeCatalogListFilters($request);

        $categoryId = request()->input('category_id');
        $hasSearch = is_string(request()->input('term')) && trim((string) request()->input('term')) !== '';

        if ($companyId > 0) {
            app(\App\Services\ProductRatingService::class)->ensureForCompany($companyId);
        }

        // Listado agrupado: recorrer categorías en el orden del catálogo (order_categories).
        if ($grouped && ! $hasSearch) {
            return $this->productsGroupedByCatalogOrder(
                $cart,
                $token,
                $customer,
                $categoryId ? (int) $categoryId : null,
                $request
            );
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 28)));
        $page = max(1, (int) $request->query('page', 1));

        /** @var CatalogController $catalogController */
        $catalogController = app(CatalogController::class);
        $paginator = $catalogController->buildProductsQuery($catalogId)
            ->with([
                'product_images.image',
                'productRating',
                'categories',
                'categoryGroups',
                'categorySubgroups',
            ])
            ->withStarOrder($customerId, OrderStatusCodes::forStar())
            ->paginate($perPage, ['products.*'], 'page', $page);

        $formatNumeric = $this->resolveFormatNumeric($companyId);

        $mappedProducts = $paginator->getCollection()->map(
            function (Product $product) use ($customer, $token, $formatNumeric) {
                return $this->mapProductForMvc(
                    $product->applyCustomerPricing($customer),
                    $customer,
                    $token,
                    $formatNumeric
                );
            }
        )->values()->all();

        return [
            'data' => $mappedProducts,
            'sections' => [],
            'grouped' => false,
            'meta' => [
                'total' => (int) $paginator->total(),
                'page' => (int) $paginator->currentPage(),
                'per_page' => (int) $paginator->perPage(),
            ],
        ];
    }

    /**
     * Productos agrupados respetando el orden de categorías del catálogo (order_categories).
     *
     * @return array{data: list<array<string, mixed>>, sections: list<array<string, mixed>>, grouped: bool, meta: array<string, int>}
     */
    private function productsGroupedByCatalogOrder(
        stdClass $cart,
        string $token,
        Customer $customer,
        ?int $filterCategoryId,
        Request $request
    ): array {
        $catalogId = (int) ($cart->catalog_id ?? 0);
        $customerId = (int) ($customer->customer_id ?? 0);
        $companyId = (int) ($cart->company_id ?? 0);

        $orderedCats = $this->categoriesOrder($cart);
        if ($filterCategoryId !== null && $filterCategoryId > 0) {
            $orderedCats = array_values(array_filter(
                $orderedCats,
                static fn (array $row): bool => (int) ($row['cat_id'] ?? 0) === $filterCategoryId
            ));
            $perPage = max(1, min(3000, (int) $request->query('per_page', 2000)));
            $page = 1;
        } else {
            $perPage = max(1, min(200, (int) $request->query('per_page', 100)));
            $page = max(1, (int) $request->query('page', 1));
        }

        $formatNumeric = $this->resolveFormatNumeric($companyId);
        $pageMapped = [];
        $seenIds = [];
        $skip = ($filterCategoryId || $page < 1) ? 0 : (($page - 1) * $perPage);
        $need = $perPage;

        // Total global del catálogo (sin recontar fila a fila tras llenar la página).
        if ($filterCategoryId) {
            $total = 0;
        } else {
            request()->merge(['category_id' => null]);
            $total = $this->productCount($cart, $request);
        }

        /** @var CatalogController $catalogController */
        $catalogController = app(CatalogController::class);

        // Cargar productos por cada categoría en el orden del catálogo.
        foreach ($orderedCats as $catRow) {
            $cid = (int) ($catRow['cat_id'] ?? 0);
            if ($cid < 1) {
                continue;
            }

            request()->merge(['category_id' => $cid]);
            $query = $catalogController->buildProductsQuery($catalogId)
                ->with([
                    'product_images.image',
                    'productRating',
                    'categories',
                    'categoryGroups',
                    'categorySubgroups',
                ]);
            if ($customerId > 0) {
                $query->withStarOrder($customerId, OrderStatusCodes::forStar());
            }

            $limit = $filterCategoryId ? min(3000, max($perPage, 1)) : 3000;
            $rows = $query->limit($limit)->get();

            foreach ($rows as $product) {
                $pid = (int) $product->pro_id;
                if ($pid < 1 || isset($seenIds[$pid])) {
                    continue;
                }
                $seenIds[$pid] = true;

                if ($filterCategoryId) {
                    $total++;
                } elseif ($skip > 0) {
                    $skip--;
                    continue;
                } elseif (count($pageMapped) >= $need) {
                    break 2;
                }

                $priced = $product->applyCustomerPricing($customer);
                $row = $this->mapProductForMvc($priced, $customer, $token, $formatNumeric);
                $row['_category_ids'] = $product->categories
                    ->pluck('cat_id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter(static fn ($id) => $id > 0)
                    ->values()
                    ->all();
                if (! in_array($cid, $row['_category_ids'], true)) {
                    $row['_category_ids'][] = $cid;
                }
                array_unshift($row['_category_ids'], $cid);
                $row['_category_ids'] = array_values(array_unique($row['_category_ids']));
                $row['_preferred_category_id'] = $cid;

                $row['_group_ids'] = $product->categoryGroups
                    ->pluck('group_id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter(static fn ($id) => $id > 0)
                    ->values()
                    ->all();
                $legacyGroup = (int) ($product->category_group_id ?? 0);
                if ($legacyGroup > 0) {
                    $row['_group_ids'][] = $legacyGroup;
                }
                $row['_group_ids'] = array_values(array_unique($row['_group_ids']));

                $row['_subgroup_ids'] = $product->categorySubgroups
                    ->pluck('subgroup_id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter(static fn ($id) => $id > 0)
                    ->values()
                    ->all();
                $legacySub = (int) ($product->category_subgroup_id ?? 0);
                if ($legacySub > 0) {
                    $row['_subgroup_ids'][] = $legacySub;
                }
                $row['_subgroup_ids'] = array_values(array_unique($row['_subgroup_ids']));

                $pageMapped[] = $row;
            }

            if ($filterCategoryId) {
                break;
            }
            if (! $filterCategoryId && $skip <= 0 && count($pageMapped) >= $need) {
                break;
            }
        }

        // Restaurar filtro de request.
        if ($filterCategoryId) {
            request()->merge(['category_id' => $filterCategoryId]);
        } else {
            $request->query->remove('category_id');
            request()->request->remove('category_id');
            // merge null no siempre limpia; forzar sin filtro
            request()->merge(['category_id' => null]);
        }

        if ($filterCategoryId) {
            $page = 1;
            $perPage = max(count($pageMapped), 1);
        }

        $sections = $this->buildTaxonomySections(
            $pageMapped,
            $companyId,
            $catalogId,
            $filterCategoryId,
            $token
        );

        $data = array_map(static function (array $row): array {
            unset(
                $row['_category_ids'],
                $row['_group_ids'],
                $row['_subgroup_ids'],
                $row['_preferred_category_id']
            );

            return $row;
        }, $pageMapped);

        return [
            'data' => $data,
            'sections' => $sections,
            'grouped' => true,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Arma secciones categoría → grupo → subgrupo a partir de productos ya mapeados.
     *
     * @param  list<array<string, mixed>>  $mappedProducts
     * @return list<array<string, mixed>>
     */
    private function buildTaxonomySections(
        array $mappedProducts,
        int $companyId,
        int $catalogId,
        ?int $filterCategoryId,
        string $token
    ): array {
        if ($mappedProducts === []) {
            return [];
        }

        $orderedCats = $this->categoriesOrder((object) [
            'company_id' => $companyId,
            'catalog_id' => $catalogId,
        ]);
        if ($filterCategoryId !== null && $filterCategoryId > 0) {
            $orderedCats = array_values(array_filter(
                $orderedCats,
                static fn (array $row): bool => (int) ($row['cat_id'] ?? 0) === $filterCategoryId
            ));
        }

        $catMeta = [];
        foreach ($orderedCats as $idx => $row) {
            $cid = (int) ($row['cat_id'] ?? 0);
            if ($cid < 1) {
                continue;
            }
            $catMeta[$cid] = [
                'category_id' => $cid,
                'category_name' => strtoupper(trim((string) ($row['category_name'] ?? ''))),
                'category_default' => (int) ($row['category_default'] ?? 0),
                'category_image' => $this->taxonomyImageUrl($row['image_uuid'] ?? $row['image'] ?? null, $token),
                // Posición en el catálogo (order_categories), no alfabética.
                'order' => $idx,
                'catalog_order' => (int) ($row['order'] ?? $idx),
            ];
        }

        // Completar categorías presentes en productos pero no en order_categories (al final).
        $extraOrder = count($catMeta);
        foreach ($mappedProducts as $prod) {
            foreach (($prod['_category_ids'] ?? []) as $cid) {
                $cid = (int) $cid;
                if ($cid < 1 || isset($catMeta[$cid])) {
                    continue;
                }
                $cat = Category::query()->with('imageRecord')->find($cid);
                $catMeta[$cid] = [
                    'category_id' => $cid,
                    'category_name' => strtoupper(trim((string) ($cat->category_name ?? ('#' . $cid)))),
                    'category_default' => (int) ($cat->category_default ?? 0),
                    'category_image' => $this->taxonomyImageUrl(
                        $cat?->imageRecord ?? $cat?->image ?? null,
                        $token
                    ),
                    'order' => $extraOrder++,
                    'catalog_order' => 9999 + $cid,
                ];
            }
        }

        $categoryIds = array_keys($catMeta);
        $groupsByCategory = [];
        if ($categoryIds !== []) {
            $groups = CategoryGroup::query()
                ->with([
                    'subgroups' => static function ($q) {
                        $q->where(function ($inner) {
                            $inner->whereNull('subgroup_status')->orWhere('subgroup_status', 1);
                        })
                            ->with('imageRecord')
                            ->orderBy('sort_order')
                            ->orderBy('subgroup_name');
                    },
                    'imageRecord',
                ])
                ->whereIn('category_id', $categoryIds)
                ->where(function ($q) {
                    $q->whereNull('group_status')->orWhere('group_status', 1);
                })
                ->orderBy('sort_order')
                ->orderBy('group_name')
                ->get();

            foreach ($groups as $group) {
                $cid = (int) $group->category_id;
                $gid = (int) $group->group_id;
                $subMap = [];
                $subgroups = [];
                foreach ($group->subgroups as $sub) {
                    $sid = (int) $sub->subgroup_id;
                    if ($sid < 1) {
                        continue;
                    }
                    $subgroups[] = [
                        'subgroup_id' => $sid,
                        'subgroup_name' => strtoupper(trim((string) ($sub->subgroup_name ?? ''))),
                        'subgroup_image' => $this->taxonomyImageUrl(
                            $sub->imageRecord ?? $sub->image ?? null,
                            $token
                        ),
                        'sort_order' => (int) ($sub->sort_order ?? 0),
                        'products' => [],
                    ];
                    $subMap[$sid] = count($subgroups) - 1;
                }
                $groupsByCategory[$cid][] = [
                    'group_id' => $gid,
                    'group_name' => strtoupper(trim((string) ($group->group_name ?? ''))),
                    'group_image' => $this->taxonomyImageUrl(
                        $group->imageRecord ?? $group->image ?? null,
                        $token
                    ),
                    'sort_order' => (int) ($group->sort_order ?? 0),
                    'subgroups' => $subgroups,
                    'products' => [],
                    '_sub_map' => $subMap,
                ];
            }
        }

        // Índice subgroup → [category_id, group_index]
        $subgroupIndex = [];
        $groupIndex = [];
        foreach ($groupsByCategory as $cid => $groups) {
            foreach ($groups as $gi => $group) {
                $groupIndex[$group['group_id']] = [$cid, $gi];
                foreach ($group['_sub_map'] as $sid => $si) {
                    $subgroupIndex[$sid] = [$cid, $gi, $si];
                }
            }
        }

        $sections = [];
        foreach ($catMeta as $cid => $meta) {
            $sections[$cid] = $meta + [
                'groups' => $groupsByCategory[$cid] ?? [],
                'products' => [],
            ];
        }
        $orphanSection = [
            'category_id' => 0,
            'category_name' => '',
            'category_default' => 1,
            'category_image' => '',
            'order' => 999999,
            'groups' => [],
            'products' => [],
        ];

        $seen = [];
        foreach ($mappedProducts as $prod) {
            $pid = (int) ($prod['pro_id'] ?? 0);
            if ($pid < 1 || isset($seen[$pid])) {
                continue;
            }
            $seen[$pid] = true;
            $clean = $prod;
            unset($clean['_category_ids'], $clean['_group_ids'], $clean['_subgroup_ids']);

            $placed = false;
            foreach ($prod['_subgroup_ids'] ?? [] as $sid) {
                if (! isset($subgroupIndex[$sid])) {
                    continue;
                }
                [$cid, $gi, $si] = $subgroupIndex[$sid];
                if ($filterCategoryId && $cid !== $filterCategoryId) {
                    continue;
                }
                if (! isset($sections[$cid])) {
                    continue;
                }
                $sections[$cid]['groups'][$gi]['subgroups'][$si]['products'][] = $clean;
                $placed = true;
                break;
            }
            if (! $placed) {
                foreach ($prod['_group_ids'] ?? [] as $gid) {
                    if (! isset($groupIndex[$gid])) {
                        continue;
                    }
                    [$cid, $gi] = $groupIndex[$gid];
                    if ($filterCategoryId && $cid !== $filterCategoryId) {
                        continue;
                    }
                    if (! isset($sections[$cid])) {
                        continue;
                    }
                    $sections[$cid]['groups'][$gi]['products'][] = $clean;
                    $placed = true;
                    break;
                }
            }
            if (! $placed) {
                $preferred = (int) ($prod['_preferred_category_id'] ?? 0);
                $catIds = $prod['_category_ids'] ?? [];
                if ($filterCategoryId) {
                    $catIds = array_values(array_filter($catIds, static fn ($id) => (int) $id === $filterCategoryId));
                    if ($catIds === []) {
                        $catIds = [$filterCategoryId];
                    }
                }
                // Elegir la categoría según orden del catálogo (menor `order` en catMeta).
                $targetCat = 0;
                if ($preferred > 0 && isset($sections[$preferred]) && (! $filterCategoryId || $preferred === $filterCategoryId)) {
                    $targetCat = $preferred;
                } else {
                    $bestOrder = PHP_INT_MAX;
                    foreach ($catIds as $cidCand) {
                        $cidCand = (int) $cidCand;
                        if ($cidCand < 1 || ! isset($sections[$cidCand])) {
                            continue;
                        }
                        $ord = (int) ($catMeta[$cidCand]['order'] ?? PHP_INT_MAX);
                        if ($ord < $bestOrder) {
                            $bestOrder = $ord;
                            $targetCat = $cidCand;
                        }
                    }
                }
                if ($targetCat > 0 && isset($sections[$targetCat])) {
                    $sections[$targetCat]['products'][] = $clean;
                } else {
                    $orphanSection['products'][] = $clean;
                }
            }
        }

        // Emitir en el mismo orden que order_categories (no alfabético).
        $out = [];
        foreach ($catMeta as $cid => $_meta) {
            if (! isset($sections[$cid])) {
                continue;
            }
            $section = $sections[$cid];
            $groupsOut = [];
            foreach ($section['groups'] as $group) {
                unset($group['_sub_map']);
                $subsOut = [];
                foreach ($group['subgroups'] as $sub) {
                    if ($sub['products'] === []) {
                        continue;
                    }
                    $subsOut[] = $sub;
                }
                $group['subgroups'] = $subsOut;
                if ($group['products'] === [] && $group['subgroups'] === []) {
                    continue;
                }
                $groupsOut[] = $group;
            }
            $section['groups'] = $groupsOut;
            if ($section['products'] === [] && $section['groups'] === []) {
                continue;
            }
            $out[] = $section;
        }

        if ($orphanSection['products'] !== []) {
            $out[] = $orphanSection;
        }

        return $out;
    }

    /**
     * URL pública de imagen de taxonomía (categoría/grupo/subgrupo).
     * La ruta exige uuid.ext (p. ej. …/images/catalog/{uuid}.webp?token=…).
     */
    private function taxonomyImageUrl(mixed $imageOrUuid, string $token): string
    {
        if ($token === '') {
            return '';
        }

        if ($imageOrUuid instanceof Image) {
            $ref = $this->imageRefFromModel($imageOrUuid);

            return $ref ? $this->catalogPublicImageUrl($ref, $token) : '';
        }

        $raw = trim((string) ($imageOrUuid ?? ''));
        if ($raw === '') {
            return '';
        }

        // Ya viene como uuid.ext
        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})\.([a-z0-9]+)$/i', $raw, $m)) {
            $ext = strtolower($m[2]);
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (! in_array($ext, ['jpg', 'png', 'gif', 'webp', 'bmp', 'avif', 'tif', 'tiff', 'heic', 'heif', 'svg', 'ico'], true)) {
                $ext = 'webp';
            }

            return $this->catalogPublicImageUrl($m[1] . '.' . $ext, $token);
        }

        if (! preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $raw, $m)) {
            return '';
        }

        $uuid = $m[1];
        $image = Image::query()->where('uuid', $uuid)->first();
        $ref = $this->imageRefFromModel($image);
        if ($ref !== null) {
            return $this->catalogPublicImageUrl($ref, $token);
        }

        // Fallback alineado con catalog_public_image_url del MVC.
        return $this->catalogPublicImageUrl($uuid . '.webp', $token);
    }

    public function productCount(stdClass $cart, Request $request): int
    {
        $catalogId = (int) ($cart->catalog_id ?? 0);
        $customerId = (int) ($cart->customer_id ?? 0);
        if ($catalogId < 1) {
            return 0;
        }

        $this->mergeCatalogListFilters($request);
        /** @var CatalogController $catalogController */
        $catalogController = app(CatalogController::class);
        $query = $catalogController->buildProductsQuery($catalogId);
        if ($customerId > 0) {
            $query->withStarOrder($customerId, OrderStatusCodes::forStar());
        }

        return (int) $query->toBase()->getCountForPagination();
    }

    /**
     * @return array<string, mixed>
     */
    public function productDetail(stdClass $cart, string $token, int $productId): array
    {
        $catalogId = (int) ($cart->catalog_id ?? 0);
        $customerId = (int) ($cart->customer_id ?? 0);
        $companyId = (int) ($cart->company_id ?? 0);

        if ($catalogId < 1 || $customerId < 1 || $productId < 1) {
            return ['success' => false, 'error' => 'invalid_request'];
        }

        $product = $this->findProductInCatalog($catalogId, $productId);
        if ($product === null) {
            return ['success' => false, 'error' => 'product_not_found'];
        }

        $customer = Customer::query()->with('customer_group')->findOrFail($customerId);
        $product->applyCustomerPricing($customer);
        $formatNumeric = $this->resolveFormatNumeric($companyId);
        $priceForCustomer = $product->getPriceForCustomer($customer);

        $line = $this->cartLineForProduct($token, $productId);

        return [
            'success' => true,
            'product' => $this->mapProductDetailForMvc($product, $customer, $token, $formatNumeric),
            'line' => $line,
            'price_for_customer' => $priceForCustomer,
            'price_for_customer_formatted' => $this->formatAmount($priceForCustomer, $formatNumeric),
            'images' => $this->mapProductImagesForModal($product, $token),
            'format_numeric' => $formatNumeric ? [
                'digits' => (int) ($formatNumeric->digits ?? 2),
                'radixPoint' => (string) ($formatNumeric->radixPoint ?? '.'),
                'groupSeparator' => (string) ($formatNumeric->groupSeparator ?? ','),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cartLineForProduct(string $token, int $productId): ?array
    {
        if ($token === '' || ! Schema::hasTable('detail_car')) {
            return null;
        }

        $row = DB::table('detail_car')
            ->where('token_car', $token)
            ->where('product_id', $productId)
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'qty' => (int) ($row->qty ?? 0),
            'comment' => (string) ($row->comment ?? ''),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapProductImagesForModal(Product $product, string $token): array
    {
        if (! $product->relationLoaded('product_images')) {
            $product->load(['product_images.image']);
        }

        $images = [];
        $productImages = $product->product_images
            ->sortBy(fn ($row) => (int) ($row->img_order ?? 999999))
            ->values();

        foreach ($productImages as $productImage) {
            $image = $productImage->image;
            if ($image === null) {
                continue;
            }

            $uuid = trim((string) ($image->uuid ?? ''));
            if ($uuid === '') {
                continue;
            }

            $ref = $this->imageRefFromModel($image);
            if ($ref === null) {
                continue;
            }

            $images[] = [
                'images' => $productImage->images ?? null,
                'url' => $this->catalogPublicImageUrl($ref, $token),
                'image_uuid' => $uuid,
                'img_order' => $productImage->img_order,
            ];
        }

        return $images;
    }

    private function findProductInCatalog(int $catalogId, int $productId): ?Product
    {
        /** @var CatalogController $catalogController */
        $catalogController = app(CatalogController::class);

        return $catalogController->buildProductsQuery($catalogId)
            ->where('products.pro_id', $productId)
            ->with(['product_images.image', 'productRating'])
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProductDetailForMvc(
        Product $product,
        Customer $customer,
        string $token,
        ?FormatNumeric $formatNumeric
    ): array {
        $mapped = $this->mapProductForMvc($product, $customer, $token, $formatNumeric);
        $mapped['descriptions'] = (string) ($product->descriptions ?? '');
        $mapped['unit_type'] = (string) ($product->unit_type ?? '');

        return $mapped;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categoriesOrder(stdClass $cart): array
    {
        $companyId = (int) ($cart->company_id ?? 0);
        $catalogId = (int) ($cart->catalog_id ?? 0);
        if ($companyId < 1 || $catalogId < 1) {
            return [];
        }

        $rows = DB::select(
            "SELECT c.cat_id AS cat_id, c.category_default, c.category_name, c.image AS image_uuid, o.`order`,
                (SELECT COUNT(DISTINCT cp.product_id) FROM category_product cp
                 INNER JOIN products p ON p.pro_id = cp.product_id
                 WHERE cp.category_id = o.category_id
                 AND p.company_id = ?
                 AND p.`status` = 1
                 AND p.name IS NOT NULL
                 AND p.sale_price > 0) AS qty_products
             FROM order_categories AS o
             INNER JOIN categories AS c ON c.cat_id = o.category_id
             WHERE o.company_id = ? AND o.catalog_id = ?
             AND o.deleted_at IS NULL
             ORDER BY (c.category_default = 1) ASC, `order` ASC",
            [$companyId, $companyId, $catalogId]
        );

        $result = [];
        $existingIds = [];
        foreach ($rows as $row) {
            $item = (array) $row;
            $catId = (int) ($item['cat_id'] ?? 0);
            if ($catId < 1 || isset($existingIds[$catId])) {
                continue;
            }
            $existingIds[$catId] = true;
            $result[] = $item;
        }
        foreach ($this->additionalCategories($companyId, $catalogId) as $extra) {
            $catId = (int) ($extra['cat_id'] ?? 0);
            if ($catId > 0 && ! isset($existingIds[$catId])) {
                $existingIds[$catId] = true;
                $result[] = $extra;
            }
        }

        return array_values(array_filter(
            $result,
            static fn (array $row): bool => (int) ($row['qty_products'] ?? 0) > 0
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categoriesList(stdClass $cart): array
    {
        $companyId = (int) ($cart->company_id ?? 0);
        $catalogId = (int) ($cart->catalog_id ?? 0);
        if ($companyId < 1 || $catalogId < 1) {
            return [];
        }

        $rows = DB::select(
            "SELECT categories.cat_id, categories.category_name, categories.category_default,
                (SELECT COUNT(p.pro_id) FROM products AS p
                 WHERE p.`status` = 1
                 AND p.name IS NOT NULL
                 AND p.sale_price > 0
                 AND FIND_IN_SET(categories.cat_id, p.category_id)) AS qty_products
             FROM categories
             INNER JOIN catalog ON FIND_IN_SET(categories.cat_id, catalog.cat_id)
             WHERE categories.company_id = ?
             AND catalog.catalog_id = ?
             AND categories.category_status = 1
             ORDER BY (category_default = 1) DESC, category_default DESC",
            [$companyId, $catalogId]
        );

        return array_values(array_filter(
            array_map(static fn ($row) => (array) $row, $rows),
            static fn (array $row): bool => (int) ($row['qty_products'] ?? 0) > 0
        ));
    }

    private function mergeCatalogListFilters(Request $request): void
    {
        $categoryId = $request->query('cat_id', $request->query('category_id'));
        $search = $request->query('search', $request->query('term'));
        $sort = $this->mapShortToSort($request->query('short'));

        request()->merge(array_filter([
            'category_id' => $categoryId !== null && $categoryId !== '' ? (int) $categoryId : null,
            'term' => is_string($search) && $search !== '' ? $search : null,
            'sort' => $sort,
        ], static fn ($v) => $v !== null && $v !== ''));
    }

    private function mapShortToSort(mixed $short): string
    {
        return match ((int) $short) {
            2 => 'name_desc',
            3 => 'sku_asc',
            4 => 'sku_desc',
            5 => 'price_asc',
            6 => 'price_desc',
            default => 'name_asc',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function additionalCategories(int $companyId, int $catalogId): array
    {
        $rows = DB::select(
            "SELECT p.aditional_category AS cat_id, c.category_default, c.category_name, c.image AS image_uuid,
                NULL AS `order`, 1 AS qty_products
             FROM (
                SELECT p.*, SUBSTRING_INDEX(SUBSTRING_INDEX(p.category_id, ',', numbers.n), ',', -1) AS aditional_category
                FROM (
                    SELECT p.*
                    FROM products AS p
                    INNER JOIN catalog AS c ON FIND_IN_SET(p.pro_id, c.product_name)
                    WHERE c.company_id = ? AND c.catalog_id = ?
                ) AS p
                INNER JOIN (
                    SELECT (@num := @num + 1) AS n
                    FROM (SELECT @num := 0) AS temp
                    CROSS JOIN (
                        SELECT DISTINCT LENGTH(category_id) - LENGTH(REPLACE(category_id, ',', '')) + 1 AS max_count
                        FROM products
                        WHERE category_id IS NOT NULL AND category_id != ''
                    ) AS counts
                ) AS numbers ON CHAR_LENGTH(p.category_id) - CHAR_LENGTH(REPLACE(p.category_id, ',', '')) >= numbers.n - 1
             ) AS p
             INNER JOIN categories AS c ON c.cat_id = p.aditional_category
             GROUP BY p.aditional_category, c.category_default, c.category_name, c.image",
            [$companyId, $catalogId]
        );

        return array_map(static fn ($row) => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProductForMvc(Product $product, Customer $customer, string $token, ?FormatNumeric $formatNumeric): array
    {
        $salePrice = $product->getPriceForCustomer($customer);
        $imageRefs = $this->allImageRefsForProduct($product);
        $imageRef = $imageRefs[0] ?? null;
        $imageUrl = $imageRef
            ? $this->catalogPublicImageUrl($imageRef, $token)
            : null;

        return [
            'pro_id' => (int) $product->pro_id,
            'product_id' => (int) ($product->product_id ?? $product->pro_id),
            'name' => (string) ($product->name ?? ''),
            'sku' => (string) ($product->sku ?? ''),
            'stock' => $product->stock,
            'sale_price' => (float) ($product->sale_price ?? 0),
            'price_reduced' => (float) ($product->price_reduced ?? 0),
            'minimum_stock' => (float) ($product->minimum_stock ?? 0),
            'notify_minimum_stock' => (int) ($product->notify_minimum_stock ?? 0),
            'category_id' => $product->category_id,
            'order' => $product->order,
            'status' => (int) ($product->status ?? 1),
            'images' => $imageRef,
            'image_uuid' => $product->product_image,
            'imagen_js' => $imageUrl,
            'implode_img' => $imageRefs !== [] ? implode(',', $imageRefs) : null,
            'cImg' => count($imageRefs),
            'salePriceRecalculado' => $this->formatAmount($salePrice, $formatNumeric),
            'reducedprice_Formatted' => $this->formatAmount((float) ($product->price_reduced ?? 0), $formatNumeric),
            'has_star_order' => (bool) ($product->has_star_order ?? false),
            'rating' => (float) ($product->rating ?? \App\Services\ProductRatingService::DEFAULT_RATING),
        ];
    }

    private function resolveFormatNumeric(int $companyId): ?FormatNumeric
    {
        if ($companyId < 1) {
            return FormatNumeric::query()->where('default', 1)->first();
        }

        $company = \App\Models\Company::query()->find($companyId);

        return $company && $company->format_id
            ? FormatNumeric::query()->find((int) $company->format_id)
            : FormatNumeric::query()->where('default', 1)->first();
    }

    private function formatAmount(float $amount, ?FormatNumeric $formatNumeric): string
    {
        if ($formatNumeric !== null
            && isset($formatNumeric->digits, $formatNumeric->radixPoint, $formatNumeric->groupSeparator)) {
            return number_format(
                $amount,
                (int) $formatNumeric->digits,
                (string) $formatNumeric->radixPoint,
                (string) $formatNumeric->groupSeparator
            );
        }

        return number_format($amount, 2, '.', ',');
    }
}
