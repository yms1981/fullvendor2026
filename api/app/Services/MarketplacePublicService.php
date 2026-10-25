<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listados públicos del marketplace MVC (compañías, categorías, productos).
 */
final class MarketplacePublicService
{
    /**
     * @return array{results: list<object>, total: int}
     */
    public function listCompanies(int $offset, int $limit): array
    {
        $base = $this->marketplaceCompaniesQuery();

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->orderByDesc('companies.company_id')
            ->offset(max(0, $offset))
            ->limit(max(1, $limit))
            ->get();

        return [
            'results' => $this->rowsToObjects($rows),
            'total' => $total,
        ];
    }

    /**
     * Home marketplace: empresas con categorías propias y productos con foto.
     *
     * @return array{
     *   companies: list<array<string, mixed>>,
     *   categories_by_company: list<array<string, mixed>>,
     *   category_carousel: list<object>,
     *   promo_products: list<object>,
     *   featured_products: list<object>
     * }
     */
    public function homeFeed(int $companyLimit = 8, int $productsPerCompany = 4, int $featuredLimit = 12): array
    {
        $companyLimit = max(1, min(24, $companyLimit));
        $productsPerCompany = max(1, min(12, $productsPerCompany));
        $featuredLimit = max(4, min(48, $featuredLimit));

        $companies = $this->marketplaceCompaniesWithCatalogQuery()
            ->orderByDesc('product_with_photo_count')
            ->orderByDesc('companies.company_id')
            ->limit($companyLimit)
            ->get();

        $companyBlocks = [];
        $categoriesByCompany = [];
        $categoryCarousel = [];
        $featuredPool = [];

        foreach ($companies as $companyRow) {
            $company = is_object($companyRow) ? $companyRow : (object) $companyRow;
            $companyId = (int) ($company->company_id ?? 0);
            if ($companyId < 1) {
                continue;
            }

            $categories = $this->listActiveCategories($companyId);
            $categorySummaries = [];
            foreach ($categories as $cat) {
                if (count($categorySummaries) >= 24) {
                    break;
                }
                $catId = (int) ($cat->cat_id ?? 0);
                if ($catId < 1) {
                    continue;
                }
                $categoryImage = trim((string) ($cat->image ?? ''));
                $cover = null;
                $coverUuid = $categoryImage;
                if ($coverUuid === '') {
                    $cover = $this->firstProductWithPhoto($companyId, $catId);
                    $coverUuid = trim((string) ($cover?->image_uuid ?? ''));
                }
                if ($coverUuid === '') {
                    continue;
                }
                $summary = (object) [
                    'cat_id' => $catId,
                    'category_name' => (string) ($cat->category_name ?? ''),
                    'company_id' => $companyId,
                    'company_name' => (string) ($company->name ?? ''),
                    'company_slug' => (string) ($company->slug_url ?? ''),
                    'company_image' => (string) ($company->image ?? ''),
                    'image_uuid' => $categoryImage !== '' ? $categoryImage : null,
                    'cover_image_uuid' => $coverUuid,
                    'cover_product_name' => $cover?->name ?? null,
                ];
                $categorySummaries[] = $summary;
            }

            // Productos para propuestas/destacados: preferir variedad entre categorías.
            $products = $this->listDiversifiedProductsWithPhotos(
                $companyId,
                $categorySummaries,
                max($productsPerCompany, 8)
            );
            $currency = $this->companyCurrencyMeta($companyId);
            foreach ($products as $p) {
                $p->company_id = $companyId;
                $p->company_name = (string) ($company->name ?? '');
                $p->company_slug = (string) ($company->slug_url ?? '');
                $p->company_image = (string) ($company->image ?? '');
                $p->currency_symbol = $currency['symbol'];
                $p->currency_code = $currency['code'];
                $featuredPool[] = $p;
            }

            $companyBlocks[] = [
                'company' => $company,
                'categories' => $categorySummaries,
                'products' => $products,
            ];

            if ($categorySummaries !== []) {
                $categoriesByCompany[] = [
                    'company_id' => $companyId,
                    'company_name' => (string) ($company->name ?? ''),
                    'company_slug' => (string) ($company->slug_url ?? ''),
                    'company_image' => (string) ($company->image ?? ''),
                    'categories' => $categorySummaries,
                ];
            }
        }

        // Si no hubo empresas con foto, igual devolver empresas marketplace (sin productos).
        if ($companyBlocks === []) {
            $fallback = $this->listCompanies(0, $companyLimit)['results'];
            foreach ($fallback as $company) {
                $companyId = (int) ($company->company_id ?? 0);
                $categories = $this->listActiveCategories($companyId);
                $companyBlocks[] = [
                    'company' => $company,
                    'categories' => $categories,
                    'products' => [],
                ];
            }
        }

        // Carrusel: mezclar categorías de varias empresas (no solo las de una).
        $categoryCarousel = $this->interleaveCategoriesByCompany($categoriesByCompany, 48);

        // Destacados: repartir productos entre empresas (round-robin) para mezclar marcas.
        $featuredProducts = $this->interleaveProductsByCompany($companyBlocks, $featuredLimit);
        if ($featuredProducts === []) {
            $featuredProducts = array_slice($featuredPool, 0, $featuredLimit);
        }
        $promoProducts = array_slice($featuredProducts, 0, min(6, count($featuredProducts)));

        return [
            'companies' => $companyBlocks,
            'categories_by_company' => $categoriesByCompany,
            'category_carousel' => $categoryCarousel,
            'promo_products' => $promoProducts,
            'featured_products' => $featuredProducts,
        ];
    }

    /**
     * Mezcla categorías de distintas empresas para el carrusel.
     *
     * @param  list<array{categories?: list<object>}>  $categoriesByCompany
     * @return list<object>
     */
    private function interleaveCategoriesByCompany(array $categoriesByCompany, int $limit): array
    {
        $queues = [];
        foreach ($categoriesByCompany as $block) {
            $cats = is_array($block['categories'] ?? null) ? array_values($block['categories']) : [];
            if ($cats !== []) {
                $queues[] = $cats;
            }
        }
        if ($queues === []) {
            return [];
        }

        $out = [];
        $seen = [];
        while (count($out) < $limit) {
            $progress = false;
            foreach ($queues as $i => $queue) {
                if ($queue === []) {
                    continue;
                }
                $cat = array_shift($queues[$i]);
                $key = (int) ($cat->company_id ?? 0).':'.(int) ($cat->cat_id ?? 0);
                if (isset($seen[$key])) {
                    $progress = true;
                    continue;
                }
                $seen[$key] = true;
                $out[] = $cat;
                $progress = true;
                if (count($out) >= $limit) {
                    break 2;
                }
            }
            if (! $progress) {
                break;
            }
        }

        return $out;
    }

    /**
     * Mezcla productos de distintas empresas (1 de A, 1 de B, …).
     * Si solo hay una empresa, diversifica por categoría.
     *
     * @param  list<array{company: object, products: list<object>}>  $companyBlocks
     * @return list<object>
     */
    private function interleaveProductsByCompany(array $companyBlocks, int $limit): array
    {
        $queues = [];
        foreach ($companyBlocks as $block) {
            $products = is_array($block['products'] ?? null) ? $block['products'] : [];
            if ($products === []) {
                continue;
            }
            $queues[] = array_values($products);
        }
        if ($queues === []) {
            return [];
        }

        // Una sola empresa: mezclar por categoría para variar el feed.
        if (count($queues) === 1) {
            $byCat = [];
            foreach ($queues[0] as $p) {
                $catKey = (string) ($p->category_id ?? '0');
                $byCat[$catKey][] = $p;
            }
            $queues = array_values($byCat);
        }

        $out = [];
        $seen = [];
        while (count($out) < $limit) {
            $progress = false;
            foreach ($queues as $i => $queue) {
                if ($queue === []) {
                    continue;
                }
                $p = array_shift($queues[$i]);
                $id = (int) ($p->pro_id ?? 0);
                if ($id > 0 && isset($seen[$id])) {
                    $progress = true;
                    continue;
                }
                if ($id > 0) {
                    $seen[$id] = true;
                }
                $out[] = $p;
                $progress = true;
                if (count($out) >= $limit) {
                    break 2;
                }
            }
            if (! $progress) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<object>
     */
    public function searchCompanies(string $keyword): array
    {
        $keyword = trim($keyword);
        $query = $this->marketplaceCompaniesQuery();

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('companies.name', 'like', '%' . $keyword . '%')
                    ->orWhere('companies.short_name', 'like', '%' . $keyword . '%');
            })->orderBy('companies.name');
        }

        return $this->rowsToObjects($query->get());
    }

    public function getCompanyBySlug(string $slug): ?Company
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        return Company::query()
            ->where('slug_url', $slug)
            ->where('status', 1)
            ->first();
    }

    /**
     * @return list<object>
     */
    public function listActiveCategories(int $companyId): array
    {
        if ($companyId < 1) {
            return [];
        }

        return Category::query()
            ->where('company_id', $companyId)
            ->where('category_status', 1)
            ->orderBy('category_name')
            ->orderBy('cat_id')
            ->get()
            ->all();
    }

    /**
     * @return array{results: list<object>, total: int}
     */
    public function listProducts(int $companyId, int $categoryId, int $offset, int $limit): array
    {
        if ($companyId < 1 || $categoryId < 1) {
            return ['results' => [], 'total' => 0];
        }

        $base = $this->productsByCategoryQuery($companyId, $categoryId);
        $total = (clone $base)->count();

        $rows = (clone $base)
            ->orderByDesc('p.pro_id')
            ->offset(max(0, $offset))
            ->limit(max(1, $limit))
            ->get();

        return [
            'results' => $this->attachCompanyCurrency($this->rowsToObjects($rows), $companyId),
            'total' => $total,
        ];
    }

    /**
     * @return list<object>
     */
    public function listProductsForCategory(int $companyId, int $categoryId): array
    {
        if ($companyId < 1 || $categoryId < 1) {
            return [];
        }

        $rows = $this->productsByCategoryQuery($companyId, $categoryId)
            ->orderByDesc('p.pro_id')
            ->get();

        return $this->attachCompanyCurrency($this->rowsToObjects($rows), $companyId);
    }

    /**
     * Hasta N productos con foto, repartidos entre categorías de la empresa.
     *
     * @param  list<object>  $categorySummaries
     * @return list<object>
     */
    public function listDiversifiedProductsWithPhotos(int $companyId, array $categorySummaries, int $limit): array
    {
        $limit = max(1, $limit);
        $out = [];
        $seen = [];
        $catIds = [];
        foreach ($categorySummaries as $cat) {
            $id = (int) ($cat->cat_id ?? 0);
            if ($id > 0) {
                $catIds[] = $id;
            }
        }
        if ($catIds === []) {
            return $this->listProductsWithPhotos($companyId, null, $limit);
        }

        $perCat = max(1, (int) ceil($limit / max(1, count($catIds))));
        foreach ($catIds as $catId) {
            foreach ($this->listProductsWithPhotos($companyId, $catId, $perCat) as $p) {
                $pid = (int) ($p->pro_id ?? 0);
                if ($pid > 0 && isset($seen[$pid])) {
                    continue;
                }
                if ($pid > 0) {
                    $seen[$pid] = true;
                }
                $out[] = $p;
                if (count($out) >= $limit) {
                    return $out;
                }
            }
        }

        if (count($out) < $limit) {
            foreach ($this->listProductsWithPhotos($companyId, null, $limit) as $p) {
                $pid = (int) ($p->pro_id ?? 0);
                if ($pid > 0 && isset($seen[$pid])) {
                    continue;
                }
                $out[] = $p;
                if (count($out) >= $limit) {
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Productos activos con imagen (opcionalmente filtrados por categoría).
     *
     * @return list<object>
     */
    public function listProductsWithPhotos(int $companyId, ?int $categoryId, int $limit): array
    {
        if ($companyId < 1) {
            return [];
        }

        $query = $this->productsBaseQuery($companyId)
            ->whereExists(function ($sub) {
                $sub->select(DB::raw('1'))
                    ->from('product_images as pimg')
                    ->whereColumn('pimg.product_id', 'p.pro_id');
            });

        if ($categoryId !== null && $categoryId > 0) {
            $this->applyCategoryFilter($query, $companyId, $categoryId);
        }

        $rows = $query
            ->orderByDesc('p.pro_id')
            ->limit(max(1, $limit))
            ->get();

        return $this->attachCompanyCurrency($this->rowsToObjects($rows), $companyId);
    }

    private function firstProductWithPhoto(int $companyId, int $categoryId): ?object
    {
        $rows = $this->listProductsWithPhotos($companyId, $categoryId, 1);

        return $rows[0] ?? null;
    }

    private function marketplaceCompaniesQuery()
    {
        $query = Company::query()
            ->from('companies')
            ->select('companies.*')
            ->where('companies.status', 1)
            ->where(function ($q) {
                $q->whereNull('companies.createdby_comp_id')
                    ->orWhere('companies.createdby_comp_id', 0);
            });

        $companyCategoryFk = null;
        if (Schema::hasTable('companies_categories')) {
            if (Schema::hasColumn('companies', 'company_category_id')) {
                $companyCategoryFk = 'company_category_id';
            } elseif (Schema::hasColumn('companies', 'category_id')) {
                $companyCategoryFk = 'category_id';
            }
        }

        if ($companyCategoryFk !== null) {
            $query->leftJoin('companies_categories as cc', 'companies.'.$companyCategoryFk, '=', 'cc.category_id')
                ->addSelect(DB::raw('cc.category_name as marketplace_category_name'));
        }

        return $query;
    }

    /**
     * Empresas marketplace ordenadas por cantidad de productos con foto.
     */
    private function marketplaceCompaniesWithCatalogQuery()
    {
        $photoCountSql = '(SELECT COUNT(DISTINCT p.pro_id)
            FROM products p
            INNER JOIN product_images pi ON pi.product_id = p.pro_id
            WHERE p.company_id = companies.company_id AND p.status = 1)';

        $query = $this->marketplaceCompaniesQuery();
        $query->addSelect(DB::raw("{$photoCountSql} as product_with_photo_count"))
            ->whereRaw("{$photoCountSql} > 0");

        return $query;
    }

    private function productsBaseQuery(int $companyId)
    {
        return DB::table('products as p')
            ->where('p.company_id', $companyId)
            ->where('p.status', 1)
            ->select([
                'p.pro_id',
                'p.category_id',
                'p.name',
                'p.sku',
                'p.sale_price',
                'p.stock',
                'p.status',
                'p.currency_type',
                DB::raw('('.$this->imageUuidSubquery().') AS image_uuid'),
                DB::raw('('.$this->imageNameSubquery().') AS productImage'),
            ]);
    }

    /**
     * Moneda de la empresa (tabla currency vía companies.currency).
     *
     * @return array{symbol: string, code: string}
     */
    private function companyCurrencyMeta(int $companyId): array
    {
        static $cache = [];
        if (isset($cache[$companyId])) {
            return $cache[$companyId];
        }

        $fallback = ['symbol' => '', 'code' => ''];
        if ($companyId < 1 || ! Schema::hasTable('currency') || ! Schema::hasColumn('companies', 'currency')) {
            return $cache[$companyId] = $fallback;
        }

        $row = DB::table('companies as co')
            ->leftJoin('currency as cu', 'cu.id', '=', 'co.currency')
            ->where('co.company_id', $companyId)
            ->select(['cu.symbol', 'cu.code'])
            ->first();

        $cache[$companyId] = [
            'symbol' => trim((string) ($row->symbol ?? '')),
            'code' => trim((string) ($row->code ?? '')),
        ];

        return $cache[$companyId];
    }

    /**
     * @param  list<object>  $products
     * @return list<object>
     */
    private function attachCompanyCurrency(array $products, int $companyId): array
    {
        $currency = $this->companyCurrencyMeta($companyId);
        foreach ($products as $p) {
            if (! is_object($p)) {
                continue;
            }
            $p->currency_symbol = $currency['symbol'];
            $p->currency_code = $currency['code'];
        }

        return $products;
    }

    private function productsByCategoryQuery(int $companyId, int $categoryId)
    {
        $query = $this->productsBaseQuery($companyId);
        $this->applyCategoryFilter($query, $companyId, $categoryId);

        return $query;
    }

    private function applyCategoryFilter($query, int $companyId, int $categoryId): void
    {
        if (Schema::hasTable('category_product')) {
            $query->whereIn('p.pro_id', function ($sub) use ($companyId, $categoryId) {
                $sub->select('cp.product_id')
                    ->from('category_product as cp')
                    ->join('categories as c', 'c.cat_id', '=', 'cp.category_id')
                    ->where('c.company_id', $companyId)
                    ->where('c.cat_id', $categoryId);
            });

            return;
        }

        if (Schema::hasColumn('products', 'category_id')) {
            $query->whereRaw('FIND_IN_SET(?, p.category_id)', [$categoryId]);

            return;
        }

        $query->whereRaw('0 = 1');
    }

    private function imageUuidSubquery(): string
    {
        return 'SELECT img.uuid FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id LIMIT 1';
    }

    private function imageNameSubquery(): string
    {
        return 'SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id LIMIT 1';
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @return list<object>
     */
    private function rowsToObjects(iterable $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = is_object($row) ? $row : (object) $row;
        }

        return $out;
    }
}
