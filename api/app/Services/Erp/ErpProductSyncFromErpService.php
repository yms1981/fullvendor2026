<?php

namespace App\Services\Erp;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Recibe listado ERP → upsert productos + imágenes por URL.
 *
 * - Sin fotos en FullVendor: descarga los links y las configura.
 * - Con fotos: si el ERP trae file_mtime más reciente (o slots solo en ERP),
 *   descarga y reemplaza; si no, solo informa la comparación.
 */
class ErpProductSyncFromErpService
{
    public function __construct(
        private ErpProductImageUploadService $imageUploader = new ErpProductImageUploadService,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $products
     * @return array{
     *     total: int,
     *     created: int,
     *     updated: int,
     *     failed: int,
     *     images_downloaded: int,
     *     images_compared: int,
     *     image_reports: list<array<string, mixed>>,
     *     errors: list<string>,
     *     results: list<array<string, mixed>>
     * }
     */
    public function sync(int $companyId, array $products): array
    {
        $summary = [
            'total' => count($products),
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'images_downloaded' => 0,
            'images_compared' => 0,
            'image_reports' => [],
            'errors' => [],
            'results' => [],
        ];

        foreach ($products as $index => $row) {
            if (! is_array($row)) {
                $summary['failed']++;
                $summary['errors'][] = "index {$index}: invalid_item";
                $summary['results'][] = ['index' => $index, 'outcome' => 'failed', 'error' => 'invalid_item'];

                continue;
            }

            try {
                $result = $this->syncOne($companyId, $row);
                if (($result['created'] ?? false) === true) {
                    $summary['created']++;
                } else {
                    $summary['updated']++;
                }
                $summary['images_downloaded'] += (int) ($result['images_downloaded'] ?? 0);
                $summary['images_compared'] += (int) ($result['images_compared'] ?? 0);
                if (isset($result['image_report']) && is_array($result['image_report'])) {
                    $summary['image_reports'][] = $result['image_report'];
                }
                $summary['results'][] = $result;
            } catch (Throwable $e) {
                $summary['failed']++;
                $sku = trim((string) ($row['sku'] ?? ''));
                $msg = ($sku !== '' ? $sku.': ' : "index {$index}: ").$e->getMessage();
                if (count($summary['errors']) < 20) {
                    $summary['errors'][] = $msg;
                }
                $summary['results'][] = [
                    'index' => $index,
                    'sku' => $sku,
                    'outcome' => 'failed',
                    'error' => $e->getMessage(),
                ];
                Log::warning('erp.products.sync.item_failed', [
                    'company_id' => $companyId,
                    'sku' => $sku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    /**
     * Solo imágenes: listado ERP {sku, images:[{url,...}]} → descarga y asigna.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{
     *     total: int,
     *     processed: int,
     *     failed: int,
     *     images_downloaded: int,
     *     images_compared: int,
     *     image_reports: list<array<string, mixed>>,
     *     errors: list<string>
     * }
     */
    public function syncImagesFromUrls(int $companyId, array $items): array
    {
        $summary = [
            'total' => count($items),
            'processed' => 0,
            'failed' => 0,
            'images_downloaded' => 0,
            'images_compared' => 0,
            'image_reports' => [],
            'errors' => [],
        ];

        foreach ($items as $index => $row) {
            if (! is_array($row)) {
                $summary['failed']++;
                $summary['errors'][] = "index {$index}: invalid_item";

                continue;
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                $summary['failed']++;
                $summary['errors'][] = "index {$index}: sku is required";

                continue;
            }

            $itemCompany = (int) ($row['company_id'] ?? $companyId);
            if ($itemCompany <= 0) {
                $itemCompany = $companyId;
            }

            $product = Product::query()
                ->where('company_id', $itemCompany)
                ->whereRaw('UPPER(TRIM(sku)) = ?', [mb_strtoupper($sku, 'UTF-8')])
                ->first();

            if (! $product) {
                $summary['failed']++;
                if (count($summary['errors']) < 20) {
                    $summary['errors'][] = $sku.': product not found';
                }

                continue;
            }

            $images = $row['images'] ?? [];
            if (! is_array($images)) {
                $images = [];
            }

            try {
                $imageOutcome = $this->handleImages($product, $images);
                if (($imageOutcome['error'] ?? null) !== null) {
                    $summary['failed']++;
                    if (count($summary['errors']) < 20) {
                        $summary['errors'][] = $sku.': '.(string) $imageOutcome['error'];
                    }

                    continue;
                }

                $summary['processed']++;
                $summary['images_downloaded'] += (int) ($imageOutcome['downloaded'] ?? 0);
                $summary['images_compared'] += (int) ($imageOutcome['compared'] ?? 0);
                if (isset($imageOutcome['report']) && is_array($imageOutcome['report'])) {
                    $summary['image_reports'][] = $imageOutcome['report'];
                }
            } catch (Throwable $e) {
                $summary['failed']++;
                if (count($summary['errors']) < 20) {
                    $summary['errors'][] = $sku.': '.$e->getMessage();
                }
                Log::warning('erp.products.images_from_urls.item_failed', [
                    'company_id' => $companyId,
                    'sku' => $sku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function syncOne(int $companyId, array $row): array
    {
        $sku = trim((string) ($row['sku'] ?? ''));
        if ($sku === '') {
            throw new \InvalidArgumentException('sku is required');
        }

        $itemCompany = (int) ($row['company_id'] ?? $companyId);
        if ($itemCompany <= 0) {
            $itemCompany = $companyId;
        }

        $product = Product::query()
            ->where('company_id', $itemCompany)
            ->whereRaw('UPPER(TRIM(sku)) = ?', [mb_strtoupper($sku, 'UTF-8')])
            ->first();

        $created = false;
        if (! $product) {
            $product = new Product;
            $product->company_id = $itemCompany;
            $product->sku = $sku;
            $product->language_id = (int) ($row['language_id'] ?? 1);
            $product->created_on = now();
            $created = true;
        }

        foreach ([
            'name' => 'name',
            'sale_price' => 'sale_price',
            'fob_price' => 'fob_price',
            'purchase_price' => 'purchase_price',
            'stock' => 'stock',
            'status' => 'status',
            'barcode' => 'barcode',
            'currency_type' => 'currency_type',
            'descriptions' => 'descriptions',
            'unit_id' => 'unit_id',
        ] as $input => $column) {
            if (array_key_exists($input, $row) && $row[$input] !== null) {
                $product->{$column} = $row[$input];
            }
        }

        try {
            $resolvedCats = $this->resolveCategoryIds($itemCompany, $row);
            if ($resolvedCats !== []) {
                $joined = implode(',', array_map(static fn (int $id): string => (string) $id, $resolvedCats));
                $product->cats_id = $joined;
                $product->cat_id = (string) $resolvedCats[0];
                $product->category_id = (string) $resolvedCats[0];
            }
        } catch (Throwable $e) {
            // Categorías no deben tumbar el upsert del producto (p. ej. SoftDeletes sin columna).
            Log::warning('erp.products.sync.category_resolve_failed', [
                'company_id' => $itemCompany,
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);
        }

        if (array_key_exists('stock', $row) && ! array_key_exists('status', $row)) {
            $product->status = ((float) $product->stock) > 0 ? 1 : 0;
        }

        $product->modified_on = now();
        $product->save();

        $images = $row['images'] ?? [];
        if (! is_array($images)) {
            $images = [];
        }

        $imageOutcome = $this->handleImages($product, $images);
        if (($imageOutcome['error'] ?? null) !== null) {
            throw new \RuntimeException((string) $imageOutcome['error']);
        }

        return [
            'index' => null,
            'sku' => (string) $product->sku,
            'pro_id' => (int) $product->pro_id,
            'created' => $created,
            'outcome' => $created ? 'created' : 'updated',
            'images_downloaded' => (int) ($imageOutcome['downloaded'] ?? 0),
            'images_compared' => (int) ($imageOutcome['compared'] ?? 0),
            'image_report' => $imageOutcome['report'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $images
     * @return array{downloaded: int, compared: int, report: array<string, mixed>|null, error: string|null}
     */
    private function handleImages(Product $product, array $images): array
    {
        $normalized = [];
        foreach ($images as $img) {
            if (! is_array($img)) {
                continue;
            }
            $url = trim((string) ($img['url'] ?? $img['link'] ?? ''));
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $normalized[] = [
                'url' => $url,
                'is_default' => filter_var($img['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'sort_order' => (int) ($img['sort_order'] ?? count($normalized) + 1),
                'file_mtime' => $this->parseMtime($img['file_mtime'] ?? $img['mtime'] ?? null),
            ];
        }

        if ($normalized === []) {
            return ['downloaded' => 0, 'compared' => 0, 'report' => null, 'error' => null];
        }

        usort($normalized, static fn (array $a, array $b) => $a['sort_order'] <=> $b['sort_order']);
        if (! collect($normalized)->contains(fn (array $r) => $r['is_default'])) {
            $normalized[0]['is_default'] = true;
        }

        $hasImages = $this->productHasUsableImages($product);
        if (! $hasImages) {
            $downloaded = $this->downloadAndAttach($product, $normalized, replace: false);
            if ($downloaded === 0) {
                $sample = $normalized[0]['url'] ?? '';

                return [
                    'downloaded' => 0,
                    'compared' => 0,
                    'report' => null,
                    'error' => 'Could not download ERP images (check URL reachability from MVC): '.$sample,
                ];
            }

            return [
                'downloaded' => $downloaded,
                'compared' => 0,
                'report' => [
                    'sku' => (string) $product->sku,
                    'pro_id' => (int) $product->pro_id,
                    'action' => 'downloaded',
                    'count' => $downloaded,
                ],
                'error' => null,
            ];
        }

        $comparisons = $this->compareImageDates($product, $normalized);
        $needsReplace = collect($comparisons)->contains(
            static fn (array $row): bool => in_array((string) ($row['verdict'] ?? ''), ['erp_newer', 'only_on_erp'], true)
        );

        if ($needsReplace) {
            $downloaded = $this->downloadAndAttach($product, $normalized, replace: true);
            if ($downloaded === 0) {
                $sample = $normalized[0]['url'] ?? '';

                return [
                    'downloaded' => 0,
                    'compared' => count($comparisons),
                    'report' => [
                        'sku' => (string) $product->sku,
                        'pro_id' => (int) $product->pro_id,
                        'action' => 'replace_failed',
                        'replaced' => false,
                        'comparisons' => $comparisons,
                    ],
                    'error' => 'ERP images are newer but MVC could not download them: '.$sample,
                ];
            }

            return [
                'downloaded' => $downloaded,
                'compared' => count($comparisons),
                'report' => [
                    'sku' => (string) $product->sku,
                    'pro_id' => (int) $product->pro_id,
                    'action' => 'replaced',
                    'replaced' => true,
                    'count' => $downloaded,
                    'comparisons' => $comparisons,
                ],
                'error' => null,
            ];
        }

        return [
            'downloaded' => 0,
            'compared' => count($comparisons),
            'report' => [
                'sku' => (string) $product->sku,
                'pro_id' => (int) $product->pro_id,
                'action' => 'compared_only',
                'replaced' => false,
                'comparisons' => $comparisons,
            ],
            'error' => null,
        ];
    }

    /**
     * @param  list<array{url:string,is_default:bool,sort_order:int,file_mtime:?\Carbon\CarbonInterface}>  $images
     */
    private function downloadAndAttach(Product $product, array $images, bool $replace = false): int
    {
        $files = [];
        $defaultIndex = 0;
        $failures = [];
        foreach ($images as $i => $img) {
            $file = $this->downloadToUploadedFile($img['url'], (string) $product->sku, $i);
            if ($file === null) {
                $failures[] = $img['url'];

                continue;
            }
            if ($img['is_default']) {
                $defaultIndex = count($files);
            }
            $files[] = $file;
        }

        if ($files === []) {
            if ($failures !== []) {
                Log::warning('erp.products.sync.image_download_all_failed', [
                    'sku' => (string) $product->sku,
                    'urls' => array_slice($failures, 0, 5),
                ]);
            }

            return 0;
        }

        $this->imageUploader->upload($product, $files, $defaultIndex, replace: $replace);

        return count($files);
    }

    private function downloadToUploadedFile(string $url, string $sku, int $index): ?UploadedFile
    {
        try {
            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->withOptions(['verify' => false])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('erp.products.sync.image_download_http', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $body = $response->body();
            if ($body === '') {
                return null;
            }

            $ext = $this->guessExtension($url, (string) $response->header('Content-Type'));
            $tmp = tempnam(sys_get_temp_dir(), 'erpimg_');
            if ($tmp === false) {
                return null;
            }
            $path = $tmp.'.'.$ext;
            rename($tmp, $path);
            file_put_contents($path, $body);

            $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $sku) ?: 'product';
            $filename .= ($index === 0 ? '' : '_PIC'.($index + 1)).'.'.$ext;

            return new UploadedFile($path, $filename, mime_content_type($path) ?: 'image/jpeg', null, true);
        } catch (Throwable $e) {
            Log::warning('erp.products.sync.image_download_failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  list<array{url:string,is_default:bool,sort_order:int,file_mtime:?\Carbon\CarbonInterface}>  $erpImages
     * @return list<array<string, mixed>>
     */
    private function compareImageDates(Product $product, array $erpImages): array
    {
        $product->loadMissing(['product_images.image', 'default_image']);
        $mvcImages = $product->product_images
            ->sortBy('img_order')
            ->values();

        $rows = [];
        $max = max(count($erpImages), $mvcImages->count());
        for ($i = 0; $i < $max; $i++) {
            $erp = $erpImages[$i] ?? null;
            $link = $mvcImages->get($i);
            $mvcImage = $link?->image;
            $mvcMtime = $this->mvcImageMtime($mvcImage);
            $erpMtime = $erp['file_mtime'] ?? null;

            $verdict = 'unknown';
            if ($erp === null) {
                $verdict = 'only_on_mvc';
            } elseif ($mvcImage === null) {
                $verdict = 'only_on_erp';
            } elseif ($erpMtime === null || $mvcMtime === null) {
                $verdict = 'missing_date';
            } elseif ($erpMtime->greaterThan($mvcMtime)) {
                $verdict = 'erp_newer';
            } elseif ($mvcMtime->greaterThan($erpMtime)) {
                $verdict = 'mvc_newer';
            } else {
                $verdict = 'same';
            }

            $rows[] = [
                'slot' => $i + 1,
                'erp_url' => $erp['url'] ?? null,
                'erp_file_mtime' => $erpMtime?->toIso8601String(),
                'erp_is_default' => (bool) ($erp['is_default'] ?? false),
                'mvc_image_id' => $mvcImage?->uuid,
                'mvc_file_mtime' => $mvcMtime?->toIso8601String(),
                'mvc_is_default' => $mvcImage && (string) $product->default_image_id === (string) $mvcImage->uuid,
                'verdict' => $verdict,
                'will_replace' => false,
            ];
        }

        return $rows;
    }

    private function mvcImageMtime(?Image $image): ?\Carbon\CarbonInterface
    {
        if (! $image) {
            return null;
        }

        $path = trim((string) ($image->path ?? ''));
        $disk = (string) ($image->disk ?? '');
        if ($path !== '' && $disk !== '') {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    $ts = Storage::disk($disk)->lastModified($path);
                    if ($ts > 0) {
                        return \Illuminate\Support\Carbon::createFromTimestampUTC($ts);
                    }
                }
            } catch (Throwable) {
                // fall through to model timestamps
            }
        }

        return $image->updated_at ?? $image->created_at;
    }

    private function productHasUsableImages(Product $product): bool
    {
        $product->loadMissing(['default_image', 'product_images.image']);

        if ($product->default_image && Image::isOnActiveStorage($product->default_image)) {
            return true;
        }

        foreach ($product->product_images as $link) {
            if ($link->image && Image::isOnActiveStorage($link->image)) {
                return true;
            }
        }

        return false;
    }

    private function parseMtime(mixed $value): ?\Carbon\CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $n = (int) $value;
            if ($n > 1_000_000_000_000) {
                $n = (int) floor($n / 1000);
            }

            return \Illuminate\Support\Carbon::createFromTimestampUTC($n);
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<int>
     */
    private function resolveCategoryIds(int $companyId, array $row): array
    {
        $names = [];
        $raw = $row['category_names'] ?? $row['category_name'] ?? null;
        if (is_array($raw)) {
            foreach ($raw as $item) {
                $name = trim((string) $item);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        } elseif (is_string($raw) && trim($raw) !== '') {
            foreach (preg_split('/[,;|]+/', $raw) ?: [] as $piece) {
                $name = trim((string) $piece);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        if ($names === []) {
            return [];
        }

        $nameColumn = \App\Support\CategoryNameColumn::name();
        $ids = [];
        $categoryQuery = static function () {
            // Evitar SoftDeletes si la tabla aún no tiene deleted_at.
            if (! \Illuminate\Support\Facades\Schema::hasColumn('categories', 'deleted_at')) {
                return Category::withoutGlobalScopes();
            }

            return Category::query();
        };

        foreach (array_values(array_unique($names)) as $name) {
            $category = $categoryQuery()
                ->where('company_id', $companyId)
                ->whereRaw('LOWER(TRIM(`'.$nameColumn.'`)) = ?', [mb_strtolower($name, 'UTF-8')])
                ->first();

            if (! $category) {
                $category = new Category;
                $category->company_id = $companyId;
                $category->{$nameColumn} = $name;
                $category->category_status = 1;
                $category->category_default = 0;
                $category->category_created_at = now();
                $category->save();
            }

            $catId = (int) $category->cat_id;
            if ($catId > 0) {
                $ids[] = $catId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function guessExtension(string $url, string $contentType): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        $contentType = strtolower($contentType);
        return match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'gif') => 'gif',
            default => 'jpg',
        };
    }
}
