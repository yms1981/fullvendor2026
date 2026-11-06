<?php

namespace App\Services\Erp;

use App\Models\Company;
use App\Models\ErpProductImageQueue;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cola de descarga de fotos ERP: encola URLs y baja una a una sin saturar.
 */
class ErpProductImageQueueService
{
    public const MAX_ATTEMPTS = 3;

    public function __construct(
        private ErpProductImageUploadService $uploader = new ErpProductImageUploadService,
    ) {}

    /**
     * Resumen de progreso para la UI MVC.
     *
     * @return array{
     *     total: int,
     *     pending: int,
     *     processing: int,
     *     downloaded: int,
     *     failed: int,
     *     skipped: int,
     *     percent: float,
     *     recent: list<array<string, mixed>>
     * }
     */
    public function progressForCompany(int $companyId, int $recentLimit = 40): array
    {
        $base = ErpProductImageQueue::query()->where('company_id', $companyId);
        $pending = (clone $base)->where('status', ErpProductImageQueue::STATUS_PENDING)->count();
        $processing = (clone $base)->where('status', ErpProductImageQueue::STATUS_PROCESSING)->count();
        $downloaded = (clone $base)->where('status', ErpProductImageQueue::STATUS_DOWNLOADED)->count();
        $failed = (clone $base)->where('status', ErpProductImageQueue::STATUS_FAILED)->count();
        $skipped = (clone $base)->where('status', ErpProductImageQueue::STATUS_SKIPPED)->count();
        $total = $pending + $processing + $downloaded + $failed + $skipped;
        $done = $downloaded + $failed + $skipped;
        $percent = $total > 0 ? round(($done / $total) * 100, 1) : 0.0;

        $recent = ErpProductImageQueue::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(max(1, min(100, $recentLimit)))
            ->get([
                'id', 'sku', 'image_url', 'status', 'attempts', 'last_error',
                'sort_order', 'is_default', 'queued_at', 'started_at', 'finished_at', 'updated_at',
            ])
            ->map(static function (ErpProductImageQueue $row): array {
                return [
                    'id' => (int) $row->id,
                    'sku' => (string) $row->sku,
                    'image_url' => (string) $row->image_url,
                    'status' => (string) $row->status,
                    'attempts' => (int) $row->attempts,
                    'last_error' => $row->last_error,
                    'sort_order' => (int) $row->sort_order,
                    'is_default' => (bool) $row->is_default,
                    'queued_at' => optional($row->queued_at)?->toDateTimeString(),
                    'started_at' => optional($row->started_at)?->toDateTimeString(),
                    'finished_at' => optional($row->finished_at)?->toDateTimeString(),
                    'updated_at' => optional($row->updated_at)?->toDateTimeString(),
                ];
            })
            ->all();

        return [
            'total' => $total,
            'pending' => $pending,
            'processing' => $processing,
            'downloaded' => $downloaded,
            'failed' => $failed,
            'skipped' => $skipped,
            'percent' => $percent,
            'active' => ($pending + $processing) > 0,
            'recent' => $recent,
        ];
    }

    /**
     * Consulta al ERP el listado de imágenes de productos sin foto y encola URLs.
     *
     * @return array{
     *     products_scanned: int,
     *     products_with_images: int,
     *     products_skipped: int,
     *     products_failed: int,
     *     queued: int,
     *     already_queued: int
     * }
     */
    public function enqueueMissingProductImages(Company $company, ErpSyncClient $client, int $productLimit = 200): array
    {
        $products = Product::query()
            ->where('company_id', $company->company_id)
            ->where(function ($q) {
                $q->whereNull('default_image_id')
                    ->orWhere('default_image_id', '');
            })
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->orderBy('pro_id')
            ->limit(max(1, $productLimit))
            ->get();

        $stats = [
            'products_scanned' => $products->count(),
            'products_with_images' => 0,
            'products_skipped' => 0,
            'products_failed' => 0,
            'queued' => 0,
            'already_queued' => 0,
        ];

        foreach ($products as $product) {
            $sku = trim((string) $product->sku);
            try {
                $payload = $client->get('/external/fv-sync/products/'.rawurlencode($sku).'/images');
                $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                $images = is_array($data['images'] ?? null) ? $data['images'] : [];
                if ($images === []) {
                    $stats['products_skipped']++;
                    $this->fileLog($company, $sku, '', 'skipped', 'ERP returned no images');

                    continue;
                }

                $stats['products_with_images']++;
                $sort = 1;
                foreach ($images as $img) {
                    if (! is_array($img)) {
                        continue;
                    }
                    $url = trim((string) ($img['url'] ?? $img['thumb_url'] ?? ''));
                    if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                        continue;
                    }
                    $isDefault = filter_var($img['is_default'] ?? ($sort === 1), FILTER_VALIDATE_BOOLEAN);
                    $result = $this->enqueueOne(
                        (int) $company->company_id,
                        (int) $product->pro_id,
                        $sku,
                        $url,
                        $sort,
                        $isDefault
                    );
                    if ($result === 'queued') {
                        $stats['queued']++;
                    } else {
                        $stats['already_queued']++;
                    }
                    $sort++;
                    if ($sort > ErpProductImageUploadService::MAX_IMAGES) {
                        break;
                    }
                }
            } catch (Throwable $e) {
                $stats['products_failed']++;
                $this->fileLog($company, $sku, '', 'failed', $e->getMessage());
                Log::warning('erp.image_queue.enqueue_product_failed', [
                    'company_id' => $company->company_id,
                    'sku' => $sku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Procesa la cola: una imagen por iteración (sin paralelismo).
     *
     * @return array{
     *     processed: int,
     *     downloaded: int,
     *     failed: int,
     *     skipped: int,
     *     remaining_pending: int
     * }
     */
    public function processPending(int $limit = 30, ?int $companyId = null): array
    {
        $limit = max(1, min(200, $limit));
        $query = ErpProductImageQueue::query()
            ->where('status', ErpProductImageQueue::STATUS_PENDING)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('id')
            ->limit($limit);

        if ($companyId !== null && $companyId > 0) {
            $query->where('company_id', $companyId);
        }

        $items = $query->get();
        $stats = [
            'processed' => 0,
            'downloaded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'remaining_pending' => 0,
        ];

        foreach ($items as $item) {
            $outcome = $this->processOne($item);
            $stats['processed']++;
            $stats[$outcome] = ($stats[$outcome] ?? 0) + 1;
        }

        $pendingQuery = ErpProductImageQueue::query()
            ->where('status', ErpProductImageQueue::STATUS_PENDING)
            ->where('attempts', '<', self::MAX_ATTEMPTS);
        if ($companyId !== null && $companyId > 0) {
            $pendingQuery->where('company_id', $companyId);
        }
        $stats['remaining_pending'] = (int) $pendingQuery->count();

        return $stats;
    }

    /**
     * Encola listado + procesa un lote (para "Ejecutar ahora").
     *
     * @return array<string, mixed>
     */
    public function enqueueAndProcess(Company $company, ErpSyncClient $client, int $productLimit = 200, int $processLimit = 40): array
    {
        $enqueue = $this->enqueueMissingProductImages($company, $client, $productLimit);
        $process = $this->processPending($processLimit, (int) $company->company_id);

        return [
            'total' => (int) ($enqueue['products_scanned'] ?? 0),
            'queued' => (int) ($enqueue['queued'] ?? 0),
            'already_queued' => (int) ($enqueue['already_queued'] ?? 0),
            'products_skipped' => (int) ($enqueue['products_skipped'] ?? 0),
            'products_failed' => (int) ($enqueue['products_failed'] ?? 0),
            'downloaded' => (int) ($process['downloaded'] ?? 0),
            'failed' => (int) ($process['failed'] ?? 0),
            'processed' => (int) ($process['processed'] ?? 0),
            'remaining_pending' => (int) ($process['remaining_pending'] ?? 0),
            'images_downloaded' => (int) ($process['downloaded'] ?? 0),
            'synced' => (int) ($process['downloaded'] ?? 0),
            'skipped' => (int) ($enqueue['products_skipped'] ?? 0) + (int) ($process['skipped'] ?? 0),
        ];
    }

    private function enqueueOne(
        int $companyId,
        int $productId,
        string $sku,
        string $url,
        int $sortOrder,
        bool $isDefault
    ): string {
        $hash = hash('sha256', $url);
        $existing = ErpProductImageQueue::query()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('url_hash', $hash)
            ->first();

        if ($existing) {
            if (in_array($existing->status, [
                ErpProductImageQueue::STATUS_DOWNLOADED,
                ErpProductImageQueue::STATUS_PENDING,
                ErpProductImageQueue::STATUS_PROCESSING,
            ], true)) {
                return 'exists';
            }
            // Reintentar fallidos/skipped
            $existing->status = ErpProductImageQueue::STATUS_PENDING;
            $existing->attempts = 0;
            $existing->last_error = null;
            $existing->queued_at = now();
            $existing->started_at = null;
            $existing->finished_at = null;
            $existing->sort_order = $sortOrder;
            $existing->is_default = $isDefault;
            $existing->save();
            $this->fileLogCompanyId($companyId, $sku, $url, 'requeued', null);

            return 'queued';
        }

        ErpProductImageQueue::query()->create([
            'company_id' => $companyId,
            'product_id' => $productId,
            'sku' => $sku,
            'image_url' => mb_substr($url, 0, 2048),
            'url_hash' => $hash,
            'sort_order' => $sortOrder,
            'is_default' => $isDefault,
            'status' => ErpProductImageQueue::STATUS_PENDING,
            'attempts' => 0,
            'queued_at' => now(),
        ]);
        $this->fileLogCompanyId($companyId, $sku, $url, 'queued', null);

        return 'queued';
    }

    /**
     * @return 'downloaded'|'failed'|'skipped'
     */
    private function processOne(ErpProductImageQueue $item): string
    {
        $item->status = ErpProductImageQueue::STATUS_PROCESSING;
        $item->attempts = (int) $item->attempts + 1;
        $item->started_at = now();
        $item->save();

        $product = Product::query()
            ->where('company_id', $item->company_id)
            ->where('pro_id', $item->product_id)
            ->first();

        if (! $product) {
            return $this->mark($item, ErpProductImageQueue::STATUS_SKIPPED, 'product not found');
        }

        // Ya tiene imagen por defecto: no saturar con descargas innecesarias.
        if (! empty($product->default_image_id) && ! $item->is_default) {
            $hasSlots = $product->product_images()->count();
            if ($hasSlots >= ErpProductImageUploadService::MAX_IMAGES) {
                return $this->mark($item, ErpProductImageQueue::STATUS_SKIPPED, 'product already has max images');
            }
        }

        try {
            $file = $this->downloadToUploadedFile(
                (string) $item->image_url,
                (string) $item->sku,
                max(0, (int) $item->sort_order - 1)
            );
            if ($file === null) {
                return $this->failOrRetry($item, 'download failed (HTTP/empty body)');
            }

            // Una sola imagen por pasada.
            $replace = empty($product->default_image_id) && (bool) $item->is_default;
            $this->uploader->upload($product, [$file], 0, replace: $replace);

            return $this->mark($item, ErpProductImageQueue::STATUS_DOWNLOADED, null);
        } catch (Throwable $e) {
            return $this->failOrRetry($item, $e->getMessage());
        }
    }

    /**
     * @return 'downloaded'|'failed'|'skipped'
     */
    private function failOrRetry(ErpProductImageQueue $item, string $error): string
    {
        if ((int) $item->attempts < self::MAX_ATTEMPTS) {
            $item->status = ErpProductImageQueue::STATUS_PENDING;
            $item->last_error = mb_substr($error, 0, 2000);
            $item->finished_at = null;
            $item->save();
            $this->fileLogCompanyId(
                (int) $item->company_id,
                (string) $item->sku,
                (string) $item->image_url,
                'retry',
                'attempt='.$item->attempts.' '.$error
            );

            return 'failed';
        }

        return $this->mark($item, ErpProductImageQueue::STATUS_FAILED, $error);
    }

    /**
     * @return 'downloaded'|'failed'|'skipped'
     */
    private function mark(ErpProductImageQueue $item, string $status, ?string $error): string
    {
        $item->status = $status;
        $item->last_error = $error !== null ? mb_substr($error, 0, 2000) : null;
        $item->finished_at = now();
        $item->save();

        $logStatus = match ($status) {
            ErpProductImageQueue::STATUS_DOWNLOADED => 'downloaded',
            ErpProductImageQueue::STATUS_SKIPPED => 'skipped',
            default => 'failed',
        };
        $this->fileLogCompanyId(
            (int) $item->company_id,
            (string) $item->sku,
            (string) $item->image_url,
            $logStatus,
            $error
        );

        return $logStatus;
    }

    private function downloadToUploadedFile(string $url, string $sku, int $index): ?UploadedFile
    {
        try {
            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->withOptions(['verify' => false])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            if ($body === '') {
                return null;
            }

            $contentType = (string) $response->header('Content-Type');
            $ext = 'jpg';
            if (str_contains($contentType, 'png')) {
                $ext = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $ext = 'webp';
            } elseif (str_contains($contentType, 'gif')) {
                $ext = 'gif';
            } elseif (preg_match('/\.(png|jpe?g|webp|gif)(\?|$)/i', $url, $m)) {
                $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
            }

            $tmp = tempnam(sys_get_temp_dir(), 'erpimgq_');
            if ($tmp === false) {
                return null;
            }
            $path = $tmp.'.'.$ext;
            rename($tmp, $path);
            file_put_contents($path, $body);

            $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $sku) ?: 'product';
            $filename .= ($index === 0 ? '' : '_PIC'.($index + 1)).'.'.$ext;

            return new UploadedFile($path, $filename, mime_content_type($path) ?: 'image/jpeg', null, true);
        } catch (Throwable) {
            return null;
        }
    }

    private function fileLog(Company $company, string $sku, string $url, string $status, ?string $detail): void
    {
        $this->fileLogCompanyId((int) $company->company_id, $sku, $url, $status, $detail);
    }

    private function fileLogCompanyId(int $companyId, string $sku, string $url, string $status, ?string $detail): void
    {
        $line = sprintf(
            '[%s] company=%d sku=%s status=%s url=%s%s',
            now()->format('Y-m-d H:i:s'),
            $companyId,
            $sku !== '' ? $sku : '-',
            $status,
            $url !== '' ? $url : '-',
            $detail !== null && $detail !== '' ? ' detail='.str_replace(["\r", "\n"], ' ', $detail) : ''
        );

        Log::channel('erp_image_queue')->info($line);
    }
}
