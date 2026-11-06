<?php

namespace App\Services\Erp;

use App\Http\Controllers\Cron\ScheduleTickController;
use App\Models\Company;
use App\Models\ErpProductImageQueue;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

        $cronLastTick = Cache::get(ScheduleTickController::CACHE_KEY_LAST_TICK);
        $cronLastTickStr = is_string($cronLastTick) ? $cronLastTick : null;
        $cronOk = false;
        if ($cronLastTickStr) {
            try {
                // Tick HTTP en los últimos 3 minutos ⇒ cron activo.
                $cronOk = \Carbon\Carbon::parse($cronLastTickStr)->gte(now()->subMinutes(3));
            } catch (Throwable) {
                $cronOk = false;
            }
        }

        return [
            'total' => $total,
            'pending' => $pending,
            'processing' => $processing,
            'downloaded' => $downloaded,
            'failed' => $failed,
            'skipped' => $skipped,
            'percent' => $percent,
            'active' => ($pending + $processing) > 0,
            'cron_last_tick_at' => $cronLastTickStr,
            'cron_ok' => $cronOk,
            'recent' => $recent,
        ];
    }

    /**
     * Encola URLs desde el listado ERP de productos (incluye images[]),
     * solo para SKUs de FullVendor que aún no tienen foto usable.
     *
     * Evita pedir /products/{sku}/images uno a uno (404 masivos + límite artificial).
     *
     * @return array{
     *     products_scanned: int,
     *     products_needing_photo: int,
     *     products_with_images: int,
     *     products_skipped: int,
     *     products_failed: int,
     *     products_not_in_fv: int,
     *     products_already_have_photo: int,
     *     queued: int,
     *     already_queued: int,
     *     invalid_urls: int
     * }
     */
    public function enqueueMissingProductImages(Company $company, ErpSyncClient $client, int $productLimit = 0): array
    {
        $stats = [
            'products_scanned' => 0,
            'products_needing_photo' => 0,
            'products_with_images' => 0,
            'products_skipped' => 0,
            'products_failed' => 0,
            'products_not_in_fv' => 0,
            'products_already_have_photo' => 0,
            'queued' => 0,
            'already_queued' => 0,
            'invalid_urls' => 0,
        ];

        try {
            $erpRows = $client->fetchAll('/external/fv-sync/products');
        } catch (Throwable $e) {
            $stats['products_failed']++;
            $this->fileLog($company, '-', '', 'failed', 'ERP products list: '.$e->getMessage());
            Log::warning('erp.image_queue.enqueue_list_failed', [
                'company_id' => $company->company_id,
                'error' => $e->getMessage(),
            ]);

            return $stats;
        }

        if ($productLimit > 0) {
            $erpRows = array_slice($erpRows, 0, max(1, $productLimit));
        }

        $stats['products_scanned'] = count($erpRows);

        // Solo productos activos (status=1) y sin imagen asignada (default_image_id vacío
        // y sin filas en product_images). No usar withoutPhoto()/storage: en prod marcaba casi todo.
        $needPhotoIds = Product::query()
            ->where('company_id', $company->company_id)
            ->where('status', 1)
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->where(function ($q) {
                $q->whereNull('default_image_id')
                    ->orWhere('default_image_id', '');
            })
            ->whereDoesntHave('product_images')
            ->pluck('pro_id')
            ->all();
        $needPhotoSet = array_fill_keys(array_map('intval', $needPhotoIds), true);
        $stats['products_needing_photo'] = count($needPhotoSet);

        $allFv = Product::query()
            ->where('company_id', $company->company_id)
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->get(['pro_id', 'sku', 'status']);

        /** @var array<string, array{pro_id:int, needs_photo:bool, active:bool}> $fvBySku */
        $fvBySku = [];
        foreach ($allFv as $product) {
            $key = mb_strtoupper(trim((string) $product->sku), 'UTF-8');
            if ($key === '' || isset($fvBySku[$key])) {
                continue;
            }
            $proId = (int) $product->pro_id;
            $fvBySku[$key] = [
                'pro_id' => $proId,
                'active' => (int) ($product->status ?? 0) === 1,
                'needs_photo' => isset($needPhotoSet[$proId]),
            ];
        }

        foreach ($erpRows as $row) {
            if (! is_array($row)) {
                $stats['products_skipped']++;
                continue;
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                $stats['products_skipped']++;
                continue;
            }

            $skuKey = mb_strtoupper($sku, 'UTF-8');
            $fv = $fvBySku[$skuKey] ?? null;
            if ($fv === null) {
                $stats['products_not_in_fv']++;
                continue;
            }
            if (! ($fv['active'] ?? false)) {
                $stats['products_skipped']++;
                continue;
            }
            if (! $fv['needs_photo']) {
                $stats['products_already_have_photo']++;
                continue;
            }
            $productId = (int) $fv['pro_id'];

            $images = $row['images'] ?? [];
            if (! is_array($images) || $images === []) {
                $stats['products_skipped']++;
                $this->fileLog($company, $sku, '', 'skipped', 'ERP product has no images[]');
                continue;
            }

            // Una sola foto (la default / primera válida): evita saturar la cola.
            $chosen = $this->pickDefaultImage($images);
            if ($chosen === null) {
                $stats['products_skipped']++;
                $stats['invalid_urls']++;
                $this->fileLog($company, $sku, '', 'skipped', 'ERP images[] had no usable URLs');
                continue;
            }

            $result = $this->enqueueOne(
                (int) $company->company_id,
                $productId,
                $sku,
                $chosen['url'],
                1,
                true
            );
            if ($result === 'queued') {
                $stats['queued']++;
                $stats['products_with_images']++;
            } else {
                $stats['already_queued']++;
                $stats['products_with_images']++;
            }
            $fvBySku[$skuKey]['needs_photo'] = false;
        }

        return $stats;
    }

    /**
     * Elige la imagen principal del ERP (is_default o la primera con URL http válida).
     *
     * @param  list<mixed>  $images
     * @return array{url:string}|null
     */
    private function pickDefaultImage(array $images): ?array
    {
        $fallback = null;
        foreach ($images as $img) {
            $url = $this->extractImageUrl($img);
            if ($url === null) {
                continue;
            }
            $isDefault = is_array($img)
                ? filter_var($img['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN)
                : false;
            if ($isDefault) {
                return ['url' => $url];
            }
            if ($fallback === null) {
                $fallback = ['url' => $url];
            }
        }

        return $fallback;
    }

    /**
     * Limpia pendientes irrelevantes con UPDATEs masivos (no cargar miles de modelos).
     *
     * @return array{skipped_inactive:int, skipped_has_photo:int, skipped_extra:int}
     */
    public function pruneIrrelevantPending(int $companyId): array
    {
        $now = now();
        $stats = [
            'skipped_inactive' => 0,
            'skipped_has_photo' => 0,
            'skipped_extra' => 0,
        ];

        $base = ErpProductImageQueue::query()
            ->where('company_id', $companyId)
            ->where('status', ErpProductImageQueue::STATUS_PENDING);

        // Producto inexistente o inactivo (status != 1).
        $stats['skipped_inactive'] = (int) (clone $base)
            ->where(function ($q) use ($companyId) {
                $q->whereNotExists(function ($sub) use ($companyId) {
                    $sub->selectRaw('1')
                        ->from('products')
                        ->whereColumn('products.pro_id', 'erp_product_image_queue.product_id')
                        ->where('products.company_id', $companyId)
                        ->where('products.status', 1);
                });
            })
            ->update([
                'status' => ErpProductImageQueue::STATUS_SKIPPED,
                'last_error' => 'product inactive or missing',
                'finished_at' => $now,
                'updated_at' => $now,
            ]);

        // Solo saltar si hay foto usable (misma regla que al encolar): imagen en disco activo,
        // no bastan default_image_id / product_images huérfanos o "no-image".
        $activeDisk = Image::defaultDisk();
        $activeDisks = Image::usesDirectoryLayout($activeDisk)
            ? ['images_local', 'legacy']
            : ['minio', 's3'];
        $stats['skipped_has_photo'] = (int) (clone $base)
            ->whereExists(function ($sub) use ($companyId, $activeDisks) {
                $sub->selectRaw('1')
                    ->from('products')
                    ->whereColumn('products.pro_id', 'erp_product_image_queue.product_id')
                    ->where('products.company_id', $companyId)
                    ->where(function ($p) use ($activeDisks) {
                        $p->where(function ($d) use ($activeDisks) {
                            $d->whereNotNull('products.default_image_id')
                                ->where('products.default_image_id', '!=', '')
                                ->where('products.default_image_id', '!=', '0')
                                ->whereExists(function ($img) use ($activeDisks) {
                                    $img->selectRaw('1')
                                        ->from('images')
                                        ->whereColumn('images.uuid', 'products.default_image_id')
                                        ->whereNotNull('images.path')
                                        ->where('images.path', '!=', '')
                                        ->where('images.path', 'not like', '%no-image%')
                                        ->whereIn('images.disk', $activeDisks);
                                });
                        })->orWhereExists(function ($pi) use ($activeDisks) {
                            $pi->selectRaw('1')
                                ->from('product_images')
                                ->join('images', 'images.uuid', '=', 'product_images.image_id')
                                ->whereColumn('product_images.product_id', 'products.pro_id')
                                ->whereNotNull('images.path')
                                ->where('images.path', '!=', '')
                                ->where('images.path', 'not like', '%no-image%')
                                ->whereIn('images.disk', $activeDisks);
                        });
                    });
            })
            ->update([
                'status' => ErpProductImageQueue::STATUS_SKIPPED,
                'last_error' => 'product already has image',
                'finished_at' => $now,
                'updated_at' => $now,
            ]);

        // Más de una pendiente por producto: dejar la de menor id.
        $keepIds = ErpProductImageQueue::query()
            ->where('company_id', $companyId)
            ->where('status', ErpProductImageQueue::STATUS_PENDING)
            ->groupBy('product_id')
            ->selectRaw('MIN(id) as id')
            ->pluck('id');

        if ($keepIds->isNotEmpty()) {
            $stats['skipped_extra'] = (int) ErpProductImageQueue::query()
                ->where('company_id', $companyId)
                ->where('status', ErpProductImageQueue::STATUS_PENDING)
                ->whereNotIn('id', $keepIds)
                ->update([
                    'status' => ErpProductImageQueue::STATUS_SKIPPED,
                    'last_error' => 'extra image skipped (one per product)',
                    'finished_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return $stats;
    }

    /**
     * Solo acepta la URL absoluta que ya devolvió el API del ERP.
     * No inventa host ni /products ni /storage: eso lo resuelve MediaStorage en fullvendorERP.
     *
     * @param  mixed  $img
     */
    private function extractImageUrl(mixed $img, string $baseUrl = ''): ?string
    {
        if (is_string($img)) {
            $url = trim($img);
        } elseif (is_array($img)) {
            $url = trim((string) (
                $img['url']
                ?? $img['link']
                ?? $img['image_url']
                ?? $img['thumb_url']
                ?? $img['src']
                ?? ''
            ));
        } else {
            return null;
        }

        if ($url === '') {
            return null;
        }

        $url = str_replace('\\', '/', $url);

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        // El ERP debe devolver http(s) absoluto (local storage o CDN).
        // Rutas relativas tipo products/... se ignoran: hay que corregir el API del ERP.
        if (preg_match('#^https?://#i', $url) !== 1) {
            return null;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
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
        @set_time_limit(0);
        $limit = max(1, min(200, $limit));
        $query = ErpProductImageQueue::query()
            ->where('status', ErpProductImageQueue::STATUS_PENDING)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            // Primero sin intentos fallidos; no bloquear el lote con reintentos.
            ->orderBy('attempts')
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
     * Si hay pendientes, drena un lote (con lock para no solapar).
     * Pensado para arranque automático: poll UI, progress API, post-enqueue.
     *
     * @return array<string, mixed>|null
     */
    public function autoDrainPending(?int $companyId = null, int $limit = 12): ?array
    {
        $pendingQuery = ErpProductImageQueue::query()
            ->where('status', ErpProductImageQueue::STATUS_PENDING)
            ->where('attempts', '<', self::MAX_ATTEMPTS);
        if ($companyId !== null && $companyId > 0) {
            $pendingQuery->where('company_id', $companyId);
        }
        if ((int) $pendingQuery->count() <= 0) {
            return null;
        }

        $lockKey = 'erp:image-queue:auto-drain:'.($companyId && $companyId > 0 ? $companyId : 'all');
        $lock = Cache::lock($lockKey, 90);
        if (! $lock->get()) {
            return null;
        }

        try {
            if ($companyId !== null && $companyId > 0) {
                $this->pruneIrrelevantPending($companyId);
            }

            return $this->processPending($limit, $companyId);
        } catch (Throwable $e) {
            Log::warning('ErpProductImageQueueService: autoDrainPending failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            try {
                $lock->release();
            } catch (Throwable) {
            }
        }
    }

    /**
     * Tras responder al HTTP: seguir drenando en segundo plano (sin congelar el panel).
     */
    public function continueDrainAfterResponse(int $companyId, int $rounds = 10, int $limit = 12): void
    {
        if ($companyId <= 0 || app()->runningInConsole()) {
            return;
        }

        dispatch(function () use ($companyId, $rounds, $limit) {
            @set_time_limit(180);
            @ignore_user_abort(true);
            $svc = app(self::class);
            for ($i = 0; $i < max(1, $rounds); $i++) {
                $stats = $svc->autoDrainPending($companyId, $limit);
                if ($stats === null || (int) ($stats['remaining_pending'] ?? 0) <= 0) {
                    break;
                }
            }
            // Si aún queda, cadena CLI independiente del navegador.
            if (ErpProductImageQueue::query()
                ->where('company_id', $companyId)
                ->where('status', ErpProductImageQueue::STATUS_PENDING)
                ->exists()) {
                $svc->spawnCliDrain($companyId, $limit);
            }
        })->afterResponse();
    }

    /**
     * Lanza `php artisan erp:process-image-queue` en background (sin UI abierta).
     * El comando encadena solo mientras queden pendientes.
     */
    public function spawnCliDrain(int $companyId = 0, int $limit = 12): void
    {
        if (! Cache::add('erp:image-queue:bg-spawn-gate', 1, 20)) {
            return;
        }

        $php = (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '')
            ? PHP_BINARY
            : 'php';
        $artisan = base_path('artisan');
        $companyOpt = $companyId > 0 ? ' --company='.$companyId : '';
        $limit = max(1, min(40, $limit));

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $cmd = 'start /B "" '.escapeshellarg($php).' '.escapeshellarg($artisan)
                    .' erp:process-image-queue --limit='.$limit.' --chain=0'.$companyOpt;
                @pclose(@popen($cmd, 'r'));

                return;
            }

            if (! function_exists('exec')) {
                return;
            }

            $cmd = sprintf(
                'nohup %s %s erp:process-image-queue --limit=%d --chain=0%s > /dev/null 2>&1 &',
                escapeshellarg($php),
                escapeshellarg($artisan),
                $limit,
                $companyOpt
            );
            @exec($cmd);
            Log::channel('scheduler')->info('erp.image_queue.spawn_cli_drain', [
                'company_id' => $companyId,
                'limit' => $limit,
            ]);
        } catch (Throwable $e) {
            Log::warning('erp.image_queue.spawn_cli_drain_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Encola listado + procesa un lote (para "Ejecutar ahora").
     *
     * @return array<string, mixed>
     */
    public function enqueueAndProcess(Company $company, ErpSyncClient $client, int $productLimit = 0, int $processLimit = 40): array
    {
        @set_time_limit(0);
        @ignore_user_abort(true);

        $companyId = (int) $company->company_id;
        $pruned = $this->pruneIrrelevantPending($companyId);
        // Primero drena lo ya pendiente (por si un Run anterior encoló y cortó el request).
        $processFirst = $this->processPending($processLimit, $companyId);
        $enqueue = $this->enqueueMissingProductImages($company, $client, $productLimit);
        $process = $this->processPending($processLimit, $companyId);
        $process = [
            'processed' => (int) ($processFirst['processed'] ?? 0) + (int) ($process['processed'] ?? 0),
            'downloaded' => (int) ($processFirst['downloaded'] ?? 0) + (int) ($process['downloaded'] ?? 0),
            'failed' => (int) ($processFirst['failed'] ?? 0) + (int) ($process['failed'] ?? 0),
            'skipped' => (int) ($processFirst['skipped'] ?? 0) + (int) ($process['skipped'] ?? 0),
            'remaining_pending' => (int) ($process['remaining_pending'] ?? 0),
        ];

        // Arranque automático aunque el panel no esté abierto:
        // - HTTP: afterResponse + artisan en background
        // - Consola/cron: solo artisan en background
        if ((int) ($process['remaining_pending'] ?? 0) > 0) {
            $this->continueDrainAfterResponse($companyId);
            $this->spawnCliDrain($companyId);
        }

        $prunedTotal = (int) ($pruned['skipped_inactive'] ?? 0)
            + (int) ($pruned['skipped_has_photo'] ?? 0)
            + (int) ($pruned['skipped_extra'] ?? 0);

        return [
            'total' => (int) ($enqueue['products_scanned'] ?? 0),
            'needing_photo' => (int) ($enqueue['products_needing_photo'] ?? 0),
            'queued' => (int) ($enqueue['queued'] ?? 0),
            'already_queued' => (int) ($enqueue['already_queued'] ?? 0),
            'products_with_images' => (int) ($enqueue['products_with_images'] ?? 0),
            'products_skipped' => (int) ($enqueue['products_skipped'] ?? 0),
            'products_failed' => (int) ($enqueue['products_failed'] ?? 0),
            'products_not_in_fv' => (int) ($enqueue['products_not_in_fv'] ?? 0),
            'products_already_have_photo' => (int) ($enqueue['products_already_have_photo'] ?? 0),
            'invalid_urls' => (int) ($enqueue['invalid_urls'] ?? 0),
            'pruned_pending' => $prunedTotal,
            'downloaded' => (int) ($process['downloaded'] ?? 0),
            'failed' => (int) ($process['failed'] ?? 0) + (int) ($enqueue['products_failed'] ?? 0),
            'processed' => (int) ($process['processed'] ?? 0),
            'remaining_pending' => (int) ($process['remaining_pending'] ?? 0),
            'images_downloaded' => (int) ($process['downloaded'] ?? 0),
            'synced' => (int) ($process['downloaded'] ?? 0),
            'skipped' => (int) ($enqueue['products_skipped'] ?? 0) + (int) ($process['skipped'] ?? 0) + $prunedTotal,
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

        $hasUsable = $this->productHasUsablePhoto($product);

        // Ya tiene galería usable: no saturar con descargas innecesarias.
        if ($hasUsable && ! $item->is_default) {
            $hasSlots = $product->product_images()->count();
            if ($hasSlots >= ErpProductImageUploadService::MAX_IMAGES) {
                return $this->mark($item, ErpProductImageQueue::STATUS_SKIPPED, 'product already has max images');
            }
        }
        if ($hasUsable && $item->is_default) {
            return $this->mark($item, ErpProductImageQueue::STATUS_SKIPPED, 'product already has usable default image');
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

            // Reemplazar si no hay foto usable y esta URL es la principal.
            $replace = ! $hasUsable && (bool) $item->is_default;
            $this->uploader->upload($product, [$file], 0, replace: $replace);

            return $this->mark($item, ErpProductImageQueue::STATUS_DOWNLOADED, null);
        } catch (Throwable $e) {
            return $this->failOrRetry($item, $e->getMessage());
        }
    }

    private function productHasUsablePhoto(Product $product): bool
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
