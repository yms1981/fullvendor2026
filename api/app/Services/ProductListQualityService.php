<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;

/**
 * Revisión al listar productos: códigos de barras repetidos, precio 0, stock negativo y unidad.
 */
class ProductListQualityService
{
    public const QUALITY_DUPLICATE_BARCODE = 'duplicate_barcode';

    public const QUALITY_ZERO_PRICE = 'zero_price';

    public const QUALITY_STOCK_FIXED = 'stock_fixed';

    public const QUALITY_UNITS_FIXED = 'units_fixed';

    private const CACHE_NEGATIVE_FIXED = 'fv:product_quality:negative_fixed:%d';

    private const CACHE_UNITS_FIXED = 'fv:product_quality:units_fixed:%d';

    /**
     * @return array{duplicate_products: int, duplicate_codes: int, zero_price: int, negative_stock_fixed: int, units_fixed: int}
     */
    public function review(int $companyId): array
    {
        $negativeStockFixed = $this->zeroNegativeStock($companyId);
        $unitsFixed = $this->assignMissingUnits($companyId);
        $duplicates = $this->countDuplicateBarcodes($companyId);

        return [
            'duplicate_products' => $duplicates['duplicate_products'],
            'duplicate_codes' => $duplicates['duplicate_codes'],
            'zero_price' => $this->countZeroPrice($companyId),
            'negative_stock_fixed' => $negativeStockFixed,
            'units_fixed' => $unitsFixed,
        ];
    }

    /**
     * IDs de productos para el filtro del listado (quality=...).
     *
     * @return list<int>
     */
    public function productIdsForFilter(int $companyId, string $quality): array
    {
        $quality = strtolower(trim($quality));

        return match ($quality) {
            self::QUALITY_DUPLICATE_BARCODE => $this->idsWithDuplicateBarcodes($companyId),
            self::QUALITY_ZERO_PRICE => $this->idsWithZeroPrice($companyId),
            self::QUALITY_STOCK_FIXED => $this->idsNegativeStockFixed($companyId),
            self::QUALITY_UNITS_FIXED => $this->idsUnitsFixed($companyId),
            default => [],
        };
    }

    /**
     * Asigna la unidad por defecto de la compañía a productos sin unit_id.
     */
    public function assignMissingUnits(int $companyId): int
    {
        $defaultUnitId = $this->resolveDefaultUnitId($companyId);
        if ($defaultUnitId < 1) {
            Cache::put(sprintf(self::CACHE_UNITS_FIXED, $companyId), [], now()->addHours(6));

            return 0;
        }

        $ids = Product::query()
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('unit_id')->orWhere('unit_id', 0);
            })
            ->pluck('pro_id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        Cache::put(
            sprintf(self::CACHE_UNITS_FIXED, $companyId),
            $ids,
            now()->addHours(6)
        );

        if ($ids === []) {
            return 0;
        }

        return (int) Product::query()
            ->whereIn('pro_id', $ids)
            ->update(['unit_id' => $defaultUnitId]);
    }

    public function resolveDefaultUnitId(int $companyId): int
    {
        if ($companyId < 1) {
            return 0;
        }

        $company = Company::query()->where('company_id', $companyId)->first(['company_id', 'default_unit_id']);
        $defaultUnitId = (int) ($company->default_unit_id ?? 0);
        if ($defaultUnitId > 0) {
            $exists = Unit::query()
                ->where('company_id', $companyId)
                ->where('unit_id', $defaultUnitId)
                ->exists();
            if ($exists) {
                return $defaultUnitId;
            }
        }

        $firstActive = (int) (Unit::query()
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->orderBy('unit_id')
            ->value('unit_id') ?? 0);

        if ($firstActive > 0) {
            if ($company && (int) ($company->default_unit_id ?? 0) !== $firstActive) {
                Company::query()
                    ->where('company_id', $companyId)
                    ->update(['default_unit_id' => $firstActive]);
            }

            return $firstActive;
        }

        return 0;
    }

    /**
     * @return list<int>
     */
    private function idsUnitsFixed(int $companyId): array
    {
        $cached = Cache::get(sprintf(self::CACHE_UNITS_FIXED, $companyId), []);
        if (! is_array($cached)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $cached), static fn (int $id) => $id > 0));
    }

    /**
     * Productos agrupados por token de barcode repetido (para el modal de corrección).
     *
     * @return list<array{barcode: string, count: int, products: list<array<string, mixed>>}>
     */
    public function duplicateBarcodeGroups(int $companyId): array
    {
        $byCode = $this->barcodeProductMap($companyId);
        $duplicateIds = [];
        $groupIds = [];
        foreach ($byCode as $token => $idsMap) {
            if (count($idsMap) < 2) {
                continue;
            }
            $ids = array_map('intval', array_keys($idsMap));
            $groupIds[$token] = $ids;
            foreach ($ids as $id) {
                $duplicateIds[$id] = true;
            }
        }

        if ($duplicateIds === []) {
            return [];
        }

        $products = Product::query()
            ->where('company_id', $companyId)
            ->whereIn('pro_id', array_keys($duplicateIds))
            ->get(['pro_id', 'sku', 'name', 'barcode', 'sale_price', 'status'])
            ->keyBy('pro_id');

        $groups = [];
        foreach ($groupIds as $token => $ids) {
            $rows = [];
            foreach ($ids as $id) {
                $p = $products->get($id);
                if (! $p) {
                    continue;
                }
                $proId = (int) $p->pro_id;
                $rows[] = [
                    'pro_id' => $proId,
                    'sku' => (string) ($p->sku ?? ''),
                    'name' => (string) ($p->name ?? ''),
                    'barcode' => (string) ($p->barcode ?? ''),
                    'sale_price' => (float) ($p->sale_price ?? 0),
                    'status' => (int) ($p->status ?? 0),
                    // Ruta relativa al origen (el modal completa con data-api-base si hace falta).
                    'image_url' => '/api/v2/public/products/'.$proId.'/image',
                ];
            }
            if ($rows === []) {
                continue;
            }
            usort($rows, static fn (array $a, array $b): int => strcmp($a['sku'], $b['sku']));
            $groups[] = [
                'barcode' => (string) $token,
                'count' => count($rows),
                'products' => $rows,
            ];
        }

        usort($groups, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return $groups;
    }

    private function zeroNegativeStock(int $companyId): int
    {
        $ids = Product::query()
            ->where('company_id', $companyId)
            ->where('stock', '<', 0)
            ->pluck('pro_id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        Cache::put(
            sprintf(self::CACHE_NEGATIVE_FIXED, $companyId),
            $ids,
            now()->addHours(6)
        );

        if ($ids === []) {
            return 0;
        }

        $fixed = Product::query()
            ->whereIn('pro_id', $ids)
            ->update(['stock' => 0]);

        app(ProductStockStatusSyncService::class)->syncProductIds($ids, $companyId);

        return (int) $fixed;
    }

    private function countZeroPrice(int $companyId): int
    {
        return Product::query()
            ->where('company_id', $companyId)
            ->where('sale_price', '=', 0)
            ->count();
    }

    /**
     * @return list<int>
     */
    private function idsWithZeroPrice(int $companyId): array
    {
        return Product::query()
            ->where('company_id', $companyId)
            ->where('sale_price', '=', 0)
            ->pluck('pro_id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function idsNegativeStockFixed(int $companyId): array
    {
        $cached = Cache::get(sprintf(self::CACHE_NEGATIVE_FIXED, $companyId), []);
        if (! is_array($cached)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $cached), static fn (int $id) => $id > 0));
    }

    /**
     * @return array{duplicate_products: int, duplicate_codes: int}
     */
    private function countDuplicateBarcodes(int $companyId): array
    {
        $ids = $this->idsWithDuplicateBarcodes($companyId);
        $codes = 0;
        $byCode = $this->barcodeProductMap($companyId);
        foreach ($byCode as $productIds) {
            if (count($productIds) >= 2) {
                $codes++;
            }
        }

        return [
            'duplicate_products' => count($ids),
            'duplicate_codes' => $codes,
        ];
    }

    /**
     * @return list<int>
     */
    private function idsWithDuplicateBarcodes(int $companyId): array
    {
        $products = [];
        foreach ($this->barcodeProductMap($companyId) as $ids) {
            if (count($ids) < 2) {
                continue;
            }
            foreach (array_keys($ids) as $proId) {
                $products[(int) $proId] = true;
            }
        }

        return array_map('intval', array_keys($products));
    }

    /**
     * @return array<string, array<int, true>>
     */
    private function barcodeProductMap(int $companyId): array
    {
        $byCode = [];
        $rows = Product::query()
            ->where('company_id', $companyId)
            ->whereNotNull('barcode')
            ->whereRaw("TRIM(barcode) <> ''")
            ->get(['pro_id', 'barcode']);

        foreach ($rows as $row) {
            foreach ($this->barcodeTokens((string) $row->barcode) as $token) {
                $byCode[$token][(int) $row->pro_id] = true;
            }
        }

        return $byCode;
    }

    /**
     * @return list<string>
     */
    private function barcodeTokens(string $raw): array
    {
        $parts = preg_split('/[\s,;|]+/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [];
        }

        $tokens = [];
        foreach ($parts as $part) {
            $token = mb_strtolower(trim((string) $part));
            if ($token !== '') {
                $tokens[$token] = true;
            }
        }

        return array_keys($tokens);
    }
}
