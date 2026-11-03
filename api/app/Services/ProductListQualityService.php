<?php

namespace App\Services;

use App\Models\Product;

/**
 * Revisión al listar productos: códigos de barras repetidos, precio 0 y stock negativo.
 */
class ProductListQualityService
{
    /**
     * @return array{duplicate_products: int, duplicate_codes: int, zero_price: int, negative_stock_fixed: int}
     */
    public function review(int $companyId): array
    {
        $negativeStockFixed = $this->zeroNegativeStock($companyId);
        $duplicates = $this->countDuplicateBarcodes($companyId);

        return [
            'duplicate_products' => $duplicates['duplicate_products'],
            'duplicate_codes' => $duplicates['duplicate_codes'],
            'zero_price' => $this->countZeroPrice($companyId),
            'negative_stock_fixed' => $negativeStockFixed,
        ];
    }

    private function zeroNegativeStock(int $companyId): int
    {
        return Product::query()
            ->where('company_id', $companyId)
            ->where('stock', '<', 0)
            ->update(['stock' => 0]);
    }

    private function countZeroPrice(int $companyId): int
    {
        return Product::query()
            ->where('company_id', $companyId)
            ->where('sale_price', '=', 0)
            ->count();
    }

    /**
     * @return array{duplicate_products: int, duplicate_codes: int}
     */
    private function countDuplicateBarcodes(int $companyId): array
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

        $products = [];
        $codes = 0;
        foreach ($byCode as $ids) {
            if (count($ids) < 2) {
                continue;
            }
            $codes++;
            foreach (array_keys($ids) as $proId) {
                $products[$proId] = true;
            }
        }

        return [
            'duplicate_products' => count($products),
            'duplicate_codes' => $codes,
        ];
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
