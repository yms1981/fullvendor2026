<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * API dedicada de sync de productos para FullVendor Warehouse.
 * Independiente de salesman / product/all. Tolerante a filas rotas.
 */
#[OA\Tag(
    name: 'WarehouseProductsSync',
    description: 'Sync paginado de productos para la app warehouse'
)]
class WarehouseProductsSyncController extends Controller
{
    private const DEFAULT_PER_PAGE = 200;

    private const MAX_PER_PAGE = 500;

    /** @var array<int, array<string, string>> companyId => [lowercaseFileName => realFileName] */
    private array $productUploadIndex = [];

    /**
     * GET /api/v3/warehouse/products/sync
     * Todos los productos de la compañía (enabled + disabled), paginados.
     */
    #[OA\Get(
        path: 'api/v3/warehouse/products/sync',
        summary: 'Sync productos warehouse (paginado, todos los status)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseProductsSync']
    )]
    public function sync(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo determinar la compañía. Envíe company_id o asocie el usuario a una compañía.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        [$page, $perPage] = $this->pagination($request);
        set_time_limit(0);

        try {
            // Query simple: sin joins frágiles. Unidad vía relación o subquery ligera.
            $query = Product::query()
                ->where('company_id', $companyId)
                ->with([
                    'unit:unit_id,unit,description',
                    'default_image' => static function ($q) {
                        $q->select('uuid', 'name', 'path', 'company_id', 'disk', 'extension', 'mime_type');
                    },
                    'product_images' => static function ($q) {
                        $q->orderByRaw('COALESCE(img_order, 999999) ASC')
                            ->orderBy('img_id')
                            ->with(['image' => static function ($iq) {
                                $iq->select('uuid', 'name', 'path', 'company_id', 'disk', 'extension', 'mime_type');
                            }]);
                    },
                    'categories' => static function ($q) {
                        $q->select('categories.cat_id', 'categories.category_name');
                    },
                ])
                ->orderByDesc('modified_on')
                ->orderBy('pro_id');

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            // Índice de archivos uploads/products/{companyId} (una vez por request).
            $this->warmProductUploadIndex($companyId);

            $data = [];
            foreach ($paginator->items() as $product) {
                try {
                    $data[] = $this->mapRow($product);
                } catch (\Throwable $e) {
                    Log::warning('warehouse.products.sync row skipped', [
                        'pro_id' => $product->pro_id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'company_id' => $companyId,
                'data' => $data,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'count' => count($data),
            ]);
        } catch (\Throwable $e) {
            Log::error('warehouse.products.sync failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error sincronizando productos warehouse',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(Product $product): array
    {
        $categories = collect($product->categories ?? []);
        $categoryNames = $categories->pluck('category_name')->filter()->implode(',');
        $categoryIds = $categories
            ->pluck('cat_id')
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->implode(',');
        $firstCatId = $categories->isNotEmpty()
            ? (int) $categories->first()->cat_id
            : null;

        $companyId = (int) ($product->company_id ?? 0);
        $imageUrls = $this->buildUploadImageUrls($product, $companyId);
        $mainImg = $imageUrls[0] ?? null;

        $iso = static function ($value): ?string {
            if ($value === null) {
                return null;
            }
            try {
                return optional($value)->toIso8601String() ?? (string) $value;
            } catch (\Throwable $e) {
                return null;
            }
        };

        return [
            'pro_id' => (int) $product->pro_id,
            'product_id' => (int) ($product->product_id ?? $product->pro_id),
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
            'unit_type' => $product->unit?->unit ?? $product->unit?->description ?? null,
            'unit' => $product->unit?->unit ?? $product->unit?->description ?? null,
            'stock' => (float) ($product->stock ?? 0),
            'minimum_stock' => (float) ($product->minimum_stock ?? 0),
            'notify_minimum_stock' => (int) ($product->notify_minimum_stock ?? 0),
            'currency_type' => $product->currency_type ?? null,
            'fob_price' => $product->fob_price ?? null,
            'locations' => $product->locations ?? null,
            'created_by' => $product->created_by ?? null,
            'created_on' => $iso($product->created_on),
            'modified_on' => $iso($product->modified_on),
            'status' => (int) ($product->status ?? 0),
            // URLs estáticas uploads/products/{companyId}/sku.ext (+ sku_picN.ext)
            'Img' => $mainImg,
            'imgURL' => $mainImg,
            'simgURL' => $mainImg,
            'images' => $imageUrls,
            'image_urls' => $imageUrls,
            'id_kor' => $product->id_kor ?? null,
            'cat_id' => $firstCatId,
            'cats_id' => $categoryIds !== '' ? $categoryIds : null,
            'sell_with_inventory' => $product->sell_with_inventory ?? null,
            'image_updated' => $product->image_updated ?? null,
            'price_reduced' => (float) ($product->price_reduced ?? 0),
            'sem_red_hasta' => $product->sem_red_hasta ?? null,
            'sem_amarillo_desde' => $product->sem_amarillo_desde ?? null,
            'sem_amarillo_hasta' => $product->sem_amarillo_hasta ?? null,
            'sem_verde_desde' => $product->sem_verde_desde ?? null,
            'sem_verde_hasta' => $product->sem_verde_hasta ?? null,
            'updated_at' => $iso($product->updated_at),
            'deleted_at' => $iso($product->deleted_at),
            'has_photo' => $mainImg !== null,
            'has_image' => $mainImg !== null,
        ];
    }

    /**
     * Construye URLs relativas:
     * - principal: /uploads/products/{companyId}/{sku}.{ext}
     * - extras:    /uploads/products/{companyId}/{sku}_pic1.{ext}, _pic2, ...
     *
     * Prioridad: archivos reales en disco; si no, genera según imágenes del producto.
     *
     * @return list<string>
     */
    private function buildUploadImageUrls(Product $product, int $companyId): array
    {
        $skuRaw = trim((string) ($product->sku ?? ''));
        if ($skuRaw === '' || $companyId <= 0) {
            return [];
        }

        // Misma regla de importación: no incluir "/" en el nombre de archivo.
        $skuSafe = str_replace(['/', '\\'], '', $skuRaw);
        if ($skuSafe === '') {
            return [];
        }

        $fromDisk = $this->findUploadUrlsOnDisk($companyId, $skuSafe);
        if ($fromDisk !== []) {
            return $fromDisk;
        }

        // Fallback: hay registro de imágenes → armar URLs convencionales.
        $imageCount = 0;
        $ext = 'webp';
        $defaultImage = $product->default_image;
        if ($defaultImage) {
            $imageCount = max($imageCount, 1);
            $ext = $this->normalizeExt($defaultImage->extension ?? $defaultImage->name);
        }
        $productImages = $product->product_images ?? collect();
        if ($productImages->isNotEmpty()) {
            $imageCount = max($imageCount, $productImages->count());
            $firstImg = $productImages->first()?->image;
            if ($firstImg) {
                $ext = $this->normalizeExt($firstImg->extension ?? $firstImg->name);
            }
        }
        if ($imageCount <= 0) {
            return [];
        }

        $urls = [];
        $urls[] = $this->uploadPublicPath($companyId, $skuSafe.'.'.$ext);
        for ($i = 1; $i < $imageCount; $i++) {
            $urls[] = $this->uploadPublicPath($companyId, $skuSafe.'_pic'.$i.'.'.$ext);
        }

        return $urls;
    }

    /**
     * @return list<string>
     */
    private function findUploadUrlsOnDisk(int $companyId, string $skuSafe): array
    {
        $index = $this->productUploadIndex[$companyId] ?? [];
        if ($index === []) {
            return [];
        }

        $skuLower = strtolower($skuSafe);
        $main = null;
        $pics = [];

        foreach ($index as $lowerName => $realName) {
            $dot = strrpos($lowerName, '.');
            if ($dot === false) {
                continue;
            }
            $base = substr($lowerName, 0, $dot);
            $ext = substr($lowerName, $dot + 1);
            if (! in_array($ext, ['webp', 'png', 'jpg', 'jpeg', 'gif', 'bmp'], true)) {
                continue;
            }
            if ($base === $skuLower) {
                $main = $realName;
                continue;
            }
            // sku_pic1 / sku_pic2 ...
            if (preg_match('/^'.preg_quote($skuLower, '/').'_pic(\d+)$/', $base, $m)) {
                $pics[(int) $m[1]] = $realName;
            }
        }

        ksort($pics, SORT_NUMERIC);
        $urls = [];
        if ($main !== null) {
            $urls[] = $this->uploadPublicPath($companyId, $main);
        }
        foreach ($pics as $file) {
            $urls[] = $this->uploadPublicPath($companyId, $file);
        }

        // Si solo hay pics sin principal, devolver pics.
        return $urls;
    }

    private function uploadPublicPath(int $companyId, string $fileName): string
    {
        return '/uploads/products/'.$companyId.'/'.$fileName;
    }

    private function normalizeExt(?string $extOrName): string
    {
        $raw = strtolower(trim((string) $extOrName));
        if ($raw === '') {
            return 'webp';
        }
        // Si viene nombre completo (sku.webp), tomar extensión.
        if (str_contains($raw, '.')) {
            $raw = pathinfo($raw, PATHINFO_EXTENSION) ?: $raw;
        }
        if ($raw === 'jpeg') {
            $raw = 'jpg';
        }
        if (! in_array($raw, ['webp', 'png', 'jpg', 'gif'], true)) {
            return 'webp';
        }

        return $raw;
    }

    private function warmProductUploadIndex(int $companyId): void
    {
        if ($companyId <= 0 || isset($this->productUploadIndex[$companyId])) {
            return;
        }

        $files = [];
        foreach ($this->uploadsRoots() as $root) {
            $dir = rtrim($root, '/\\').DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.$companyId;
            if (! is_dir($dir)) {
                continue;
            }
            $list = @scandir($dir);
            if ($list === false) {
                continue;
            }
            foreach ($list as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $full = $dir.DIRECTORY_SEPARATOR.$name;
                if (! is_file($full)) {
                    continue;
                }
                $files[strtolower($name)] = $name;
            }
            if ($files !== []) {
                break;
            }
        }

        $this->productUploadIndex[$companyId] = $files;
        Log::info('warehouse.products.sync upload index', [
            'company_id' => $companyId,
            'files' => count($files),
        ]);
    }

    /**
     * @return list<string>
     */
    private function uploadsRoots(): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [\App\Support\UploadsPath::root()],
            \App\Support\UploadsPath::candidateRoots(),
            [public_path('uploads')]
        ))));
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
}
