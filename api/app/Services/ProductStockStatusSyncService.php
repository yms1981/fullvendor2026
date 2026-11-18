<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Con "Sell with Inventory" activo en la empresa:
 * stock > 0 → producto activo (status=1)
 * stock <= 0 → producto inactivo (status=0)
 */
class ProductStockStatusSyncService
{
    /** @var array<int, bool> */
    private static array $sellWithInventoryCache = [];

    public static function forgetCompanyCache(?int $companyId = null): void
    {
        if ($companyId === null) {
            self::$sellWithInventoryCache = [];

            return;
        }

        unset(self::$sellWithInventoryCache[$companyId]);
    }

    public function companySellsWithInventory(int $companyId): bool
    {
        if ($companyId < 1) {
            return false;
        }

        if (! array_key_exists($companyId, self::$sellWithInventoryCache)) {
            self::$sellWithInventoryCache[$companyId] = (int) (Company::query()
                ->where('company_id', $companyId)
                ->value('sell_with_inventory') ?? 0) === 1;
        }

        return self::$sellWithInventoryCache[$companyId];
    }

    public static function statusFromStock(float $stock): int
    {
        return $stock > 0 ? 1 : 0;
    }

    /**
     * Ajusta status en memoria si la empresa vende con inventario.
     * No guarda; pensado para el evento saving del modelo.
     */
    public function applyToProduct(Product $product): bool
    {
        $companyId = (int) ($product->company_id ?? 0);
        if (! $this->companySellsWithInventory($companyId)) {
            return false;
        }

        $desired = self::statusFromStock((float) ($product->stock ?? 0));
        if ((int) ($product->status ?? 0) === $desired) {
            return false;
        }

        $product->status = $desired;

        return true;
    }

    /**
     * Tras cambios de stock que no disparan eventos Eloquent (decrement/update query).
     *
     * @param  list<int>  $productIds  pro_id
     */
    public function syncProductIds(array $productIds, int $companyId): int
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === [] || ! $this->companySellsWithInventory($companyId)) {
            return 0;
        }

        $deactivated = Product::query()
            ->where('company_id', $companyId)
            ->whereIn('pro_id', $productIds)
            ->where('stock', '<=', 0)
            ->where('status', '!=', 0)
            ->update(['status' => 0, 'modified_on' => now()]);

        $activated = Product::query()
            ->where('company_id', $companyId)
            ->whereIn('pro_id', $productIds)
            ->where('stock', '>', 0)
            ->where('status', '!=', 1)
            ->update(['status' => 1, 'modified_on' => now()]);

        return (int) $deactivated + (int) $activated;
    }

    /**
     * Sincroniza todos los productos de una empresa (p. ej. al activar Sell with Inventory).
     */
    public function syncCompany(int $companyId): int
    {
        self::forgetCompanyCache($companyId);

        if (! $this->companySellsWithInventory($companyId)) {
            return 0;
        }

        return (int) DB::transaction(function () use ($companyId) {
            $deactivated = Product::query()
                ->where('company_id', $companyId)
                ->where('stock', '<=', 0)
                ->where('status', '!=', 0)
                ->update(['status' => 0, 'modified_on' => now()]);

            $activated = Product::query()
                ->where('company_id', $companyId)
                ->where('stock', '>', 0)
                ->where('status', '!=', 1)
                ->update(['status' => 1, 'modified_on' => now()]);

            return (int) $deactivated + (int) $activated;
        });
    }
}
