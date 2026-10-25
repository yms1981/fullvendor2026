<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Models\Product;
use App\Models\ProductRating;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mantiene product_ratings por empresa: si está vacía (o incompleta),
 * rellena desde ventas; sin ventas usa valoración estándar.
 */
final class ProductRatingService
{
    public const DEFAULT_RATING = 3.5;

    private const CACHE_TTL_SECONDS = 600;

    /**
     * Asegura que la empresa tenga ratings para todos sus productos.
     */
    public function ensureForCompany(int $companyId, bool $force = false): void
    {
        if ($companyId < 1 || ! Schema::hasTable('product_ratings')) {
            return;
        }

        $cacheKey = "product_ratings_ready_{$companyId}";

        if (! $force && Cache::get($cacheKey) === 1) {
            return;
        }

        $productCount = Product::query()->where('company_id', $companyId)->count();
        if ($productCount < 1) {
            Cache::put($cacheKey, 1, self::CACHE_TTL_SECONDS);

            return;
        }

        $ratingCount = ProductRating::query()->where('company_id', $companyId)->count();

        if ($force || $ratingCount === 0) {
            $this->syncCompany($companyId, true);
            Cache::put($cacheKey, 1, self::CACHE_TTL_SECONDS);

            return;
        }

        if ($ratingCount < $productCount) {
            $this->syncCompany($companyId, false);
        }

        Cache::put($cacheKey, 1, self::CACHE_TTL_SECONDS);
    }

    /**
     * Garantiza rating de un producto concreto (p. ej. al crearlo).
     */
    public function ensureForProduct(int $companyId, int $productId): void
    {
        if ($companyId < 1 || $productId < 1 || ! Schema::hasTable('product_ratings')) {
            return;
        }

        $exists = ProductRating::query()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return;
        }

        $sales = $this->salesByProduct($companyId);
        $maxQty = $this->maxSalesQty($sales);
        $row = $sales[$productId] ?? ['qty' => 0.0, 'amount' => 0.0];
        $payload = $this->buildRow($companyId, $productId, (float) $row['qty'], (float) $row['amount'], $maxQty);

        ProductRating::query()->updateOrCreate(
            ['company_id' => $companyId, 'product_id' => $productId],
            $payload
        );

        Cache::forget("product_ratings_ready_{$companyId}");
    }

    /**
     * @param bool $replaceAll Si true, recalcula todos; si false, solo productos sin fila.
     */
    public function syncCompany(int $companyId, bool $replaceAll = true): int
    {
        if ($companyId < 1 || ! Schema::hasTable('product_ratings')) {
            return 0;
        }

        $sales = $this->salesByProduct($companyId);
        $maxQty = $this->maxSalesQty($sales);

        $existingIds = [];
        if (! $replaceAll) {
            $existingIds = ProductRating::query()
                ->where('company_id', $companyId)
                ->pluck('product_id')
                ->map(static fn ($id) => (int) $id)
                ->all();
            $existingIds = array_flip($existingIds);
        }

        $now = now()->toDateTimeString();
        $batch = [];
        $written = 0;

        Product::query()
            ->where('company_id', $companyId)
            ->orderBy('pro_id')
            ->select(['pro_id'])
            ->chunkById(500, function ($products) use (
                $companyId,
                $sales,
                $maxQty,
                $replaceAll,
                $existingIds,
                $now,
                &$batch,
                &$written
            ) {
                foreach ($products as $product) {
                    $productId = (int) $product->pro_id;
                    if (! $replaceAll && isset($existingIds[$productId])) {
                        continue;
                    }
                    $row = $sales[$productId] ?? ['qty' => 0.0, 'amount' => 0.0];
                    $payload = $this->buildRow(
                        $companyId,
                        $productId,
                        (float) $row['qty'],
                        (float) $row['amount'],
                        $maxQty
                    );
                    $batch[] = array_merge($payload, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    if (count($batch) >= 250) {
                        $this->upsertBatch($batch);
                        $written += count($batch);
                        $batch = [];
                    }
                }
            }, 'pro_id');

        if ($batch !== []) {
            $this->upsertBatch($batch);
            $written += count($batch);
        }

        Cache::forget("product_ratings_ready_{$companyId}");

        return $written;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function upsertBatch(array $rows): void
    {
        ProductRating::query()->upsert(
            $rows,
            ['company_id', 'product_id'],
            ['rating', 'sales_count', 'sales_amount', 'source', 'updated_at']
        );
    }

    /**
     * @return array{company_id: int, product_id: int, rating: float, sales_count: float, sales_amount: float, source: string}
     */
    private function buildRow(
        int $companyId,
        int $productId,
        float $qty,
        float $amount,
        float $maxQty
    ): array {
        if ($qty <= 0) {
            return [
                'company_id' => $companyId,
                'product_id' => $productId,
                'rating' => self::DEFAULT_RATING,
                'sales_count' => 0.0,
                'sales_amount' => 0.0,
                'source' => 'standard',
            ];
        }

        $ratio = $maxQty > 0 ? min(1.0, $qty / $maxQty) : 0.0;
        // Productos con ventas: 3.8 .. 5.0 según volumen relativo.
        $rating = round(3.8 + ($ratio * 1.2), 1);

        return [
            'company_id' => $companyId,
            'product_id' => $productId,
            'rating' => $rating,
            'sales_count' => round($qty, 2),
            'sales_amount' => round($amount, 2),
            'source' => 'sales',
        ];
    }

    /**
     * @return array<int, array{qty: float, amount: float}>
     */
    private function salesByProduct(int $companyId): array
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_details')) {
            return [];
        }

        $statusIds = OrderStatusCodes::forStar();

        $query = DB::table('orders as o')
            ->join('order_details as od', 'od.order_id', '=', 'o.order_id')
            ->where('o.company_id', $companyId)
            ->whereNull('o.deleted_at')
            ->whereNull('od.deleted_at')
            ->whereIn('o.status_id', $statusIds)
            ->groupBy('od.product_id')
            ->select([
                'od.product_id',
                DB::raw('COALESCE(SUM(od.qty), 0) as qty'),
                DB::raw('COALESCE(SUM(od.sale_price * od.qty), 0) as amount'),
            ]);

        $out = [];
        foreach ($query->get() as $row) {
            $pid = (int) ($row->product_id ?? 0);
            if ($pid < 1) {
                continue;
            }
            $out[$pid] = [
                'qty' => (float) ($row->qty ?? 0),
                'amount' => (float) ($row->amount ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param array<int, array{qty: float, amount: float}> $sales
     */
    private function maxSalesQty(array $sales): float
    {
        $max = 0.0;
        foreach ($sales as $row) {
            $qty = (float) ($row['qty'] ?? 0);
            if ($qty > $max) {
                $max = $qty;
            }
        }

        return $max;
    }
}
