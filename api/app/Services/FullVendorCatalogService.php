<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\CatalogCategory;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\OrderCategory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Mantiene el catálogo por defecto ("FullVendor Catalog") de una empresa
 * sincronizado con sus categorías activas.
 *
 * Reemplaza la lógica legacy del MVC (Helper::creat_fullvendorcatalog /
 * updateorder_catalogo / syncFullVendorCatalogCategory) para que el origen
 * de verdad sea la API y el MVC no acceda a la base de datos directamente.
 *
 * Tablas involucradas:
 *   - catalog            (catálogo default_catalog=1 de la empresa)
 *   - order_categories   (orden de categorías dentro de ese catálogo)
 *   - catalog_category   (relación catálogo ↔ categoría que consume el CRUD de catálogos)
 */
final class FullVendorCatalogService
{
    const CATALOG_NAME = 'FullVendor Catalog';

    /**
     * Asigna productos sin fila en category_product (para categorías de la empresa)
     * a la categoría por defecto indicada.
     */
    public function linkProductsWithoutCompanyCategory(int $companyId, int $defaultCategoryId): void
    {
        if ($companyId <= 0 || $defaultCategoryId <= 0 || ! Schema::hasTable('category_product')) {
            return;
        }

        $linkedProductIds = CategoryProduct::query()
            ->join('categories', 'categories.cat_id', '=', 'category_product.category_id')
            ->where('categories.company_id', $companyId)
            ->pluck('category_product.product_id');

        $missingProductIds = Product::query()
            ->where('company_id', $companyId)
            ->when($linkedProductIds->isNotEmpty(), fn ($q) => $q->whereNotIn('pro_id', $linkedProductIds))
            ->pluck('pro_id');

        if ($missingProductIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $missingProductIds->map(static fn (int $productId) => [
            'product_id'  => $productId,
            'category_id' => $defaultCategoryId,
            'created_at'  => $now,
            'updated_at'  => $now,
        ])->all();

        CategoryProduct::query()->insert($rows);
    }

    /**
     * Sincroniza el catálogo FullVendor de la empresa con sus categorías activas.
     *
     * @param int $companyId
     * @return Catalog|null El catálogo por defecto resultante, o null si no aplica.
     */
    public function sync(int $companyId): ?Catalog
    {
        if ($companyId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($companyId) {
            // Solo categorías reales y activas de la empresa (existencia en `categories`).
            $activeCategoryIds = Category::where('company_id', $companyId)
                ->where('category_status', 1)
                ->pluck('cat_id')
                ->map(static fn ($id) => (int) $id)
                ->all();

            $catIdString = implode(',', $activeCategoryIds);

            $defaultCategory = Category::where('company_id', $companyId)
                ->where('category_default', 1)
                ->first();

            // Incluir soft-deleted: si se “eliminó” el default, restaurarlo en lugar de crear otro.
            $catalog = Catalog::withTrashed()
                ->where('company_id', $companyId)
                ->where('default_catalog', 1)
                ->first();

            if ($catalog) {
                if ($catalog->trashed()) {
                    $catalog->restore();
                }
                $catalog->update([
                    'cat_id' => $catIdString,
                    'catalog_name' => self::CATALOG_NAME,
                    'status' => 1,
                    'default_catalog' => 1,
                ]);
            } else {
                $catalog = Catalog::create([
                    'cat_id'          => $catIdString,
                    'company_id'      => $companyId,
                    'catalog_name'    => self::CATALOG_NAME,
                    'status'          => 1,
                    'default_catalog' => 1,
                ]);
            }

            // Quitar de order_categories / catalog_category IDs que ya no existen o están inactivos.
            $this->pruneStaleCatalogCategories($companyId, $catalog, $activeCategoryIds);
            $this->updateOrderCategories($companyId, $catalog, $defaultCategory);
            $this->cleanOrphanOrderCategories($companyId);
            $this->syncCatalogCategory($companyId, $catalog);

            return $catalog;
        });
    }

    /**
     * Elimina vínculos a categorías inexistentes o inactivas (integridad tras borrados manuales en BD).
     *
     * @param list<int> $activeCategoryIds
     */
    protected function pruneStaleCatalogCategories(int $companyId, Catalog $catalog, array $activeCategoryIds): void
    {
        $catalogId = (int) $catalog->catalog_id;
        $valid = $activeCategoryIds !== [] ? $activeCategoryIds : [0];

        OrderCategory::withTrashed()
            ->where('company_id', $companyId)
            ->where('catalog_id', $catalogId)
            ->whereNotIn('category_id', $valid)
            ->forceDelete();

        CatalogCategory::withTrashed()
            ->where('catalog_id', $catalogId)
            ->whereNotIn('category_id', $valid)
            ->forceDelete();
    }

    /**
     * Inserta en order_categories las categorías activas (no por defecto) que
     * falten y deja la categoría por defecto al final del orden.
     */
    protected function updateOrderCategories(int $companyId, Catalog $catalog, ?Category $defaultCategory): void
    {
        $catalogId = (int) $catalog->catalog_id;

        // Categorías activas que NO son la categoría por defecto (category_default = 1).
        $companyCategoryIds = Category::where('company_id', $companyId)
            ->where('category_status', 1)
            ->where(function ($q) {
                $q->whereNull('category_default')
                    ->orWhere('category_default', '<>', 1);
            })
            ->pluck('cat_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $existingCount = OrderCategory::where('company_id', $companyId)
            ->where('catalog_id', $catalogId)
            ->count();

        if ($existingCount <= 0) {
            $order = 1;
            foreach ($companyCategoryIds as $catId) {
                OrderCategory::create([
                    'category_id' => $catId,
                    'catalog_id'  => $catalogId,
                    'company_id'  => $companyId,
                    'order'       => $order++,
                ]);
            }

            if ($defaultCategory) {
                OrderCategory::create([
                    'category_id' => $defaultCategory->cat_id,
                    'catalog_id'  => $catalogId,
                    'company_id'  => $companyId,
                    'order'       => $order,
                ]);
            }

            return;
        }

        $existingCategoryIds = OrderCategory::where('company_id', $companyId)
            ->where('catalog_id', $catalogId)
            ->pluck('category_id')
            ->all();

        $missing = array_values(array_diff($companyCategoryIds, $existingCategoryIds));

        $nextOrder = $existingCount;
        foreach ($missing as $catId) {
            OrderCategory::create([
                'category_id' => $catId,
                'catalog_id'  => $catalogId,
                'company_id'  => $companyId,
                'order'       => $nextOrder++,
            ]);
        }

        if ($defaultCategory) {
            if (!in_array($defaultCategory->cat_id, $existingCategoryIds)) {
                OrderCategory::create([
                    'category_id' => $defaultCategory->cat_id,
                    'catalog_id'  => $catalogId,
                    'company_id'  => $companyId,
                    'order'       => $nextOrder + 1,
                ]);
            } elseif (count($missing) > 0) {
                OrderCategory::where('company_id', $companyId)
                    ->where('catalog_id', $catalogId)
                    ->where('category_id', $defaultCategory->cat_id)
                    ->update(['order' => $nextOrder]);
            }
        }
    }

    /**
     * Elimina filas de order_categories huérfanas (catalog_id/category_id NULL
     * o que apuntan a un catálogo inexistente de la empresa).
     */
    protected function cleanOrphanOrderCategories(int $companyId): void
    {
        $validCatalogIds = Catalog::where('company_id', $companyId)
            ->pluck('catalog_id')
            ->all();

        OrderCategory::withTrashed()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($validCatalogIds) {
                $query->whereNull('catalog_id')
                    ->orWhereNull('category_id')
                    ->orWhereNotIn('catalog_id', $validCatalogIds);
            })
            ->forceDelete();
    }

    /**
     * Reemplaza las categorías del catálogo FullVendor (agregar / quitar / reordenar).
     * Solo admite categorías activas de la empresa. La categoría por defecto queda al final.
     *
     * @param list<int|string> $orderedCategoryIds
     */
    public function setCategories(Catalog $catalog, array $orderedCategoryIds): void
    {
        if ((int) ($catalog->default_catalog ?? 0) !== 1) {
            return;
        }

        $companyId = (int) $catalog->company_id;
        $catalogId = (int) $catalog->catalog_id;

        $defaultCategory = Category::where('company_id', $companyId)
            ->where('category_default', 1)
            ->first();
        $defaultId = $defaultCategory ? (int) $defaultCategory->cat_id : 0;

        $allowedIds = Category::where('company_id', $companyId)
            ->where('category_status', 1)
            ->when($defaultId > 0, fn ($q) => $q->where('cat_id', '!=', $defaultId))
            ->pluck('cat_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
        $allowedSet = array_flip($allowedIds);

        $requestedIds = [];
        foreach ($orderedCategoryIds as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0 || $id === $defaultId || ! isset($allowedSet[$id]) || isset($requestedIds[$id])) {
                continue;
            }
            $requestedIds[$id] = true;
        }
        $finalOrderIds = array_keys($requestedIds);

        if ($finalOrderIds === []) {
            throw new \InvalidArgumentException('catalogo_default_reorder_only');
        }

        DB::transaction(function () use ($companyId, $catalogId, $finalOrderIds, $defaultId, $catalog) {
            // withTrashed: filas soft-deleted siguen ocupando unique_catalog_order.
            CatalogCategory::withTrashed()->where('catalog_id', $catalogId)->forceDelete();
            // forceDelete: soft-delete + reinsert acumulaba filas y triplicaba el sidebar del catálogo público.
            OrderCategory::withTrashed()
                ->where('company_id', $companyId)
                ->where('catalog_id', $catalogId)
                ->forceDelete();

            $order = 1;
            foreach ($finalOrderIds as $categoryId) {
                CatalogCategory::create([
                    'catalog_id' => $catalogId,
                    'category_id' => $categoryId,
                    'order' => $order,
                ]);
                OrderCategory::create([
                    'category_id' => $categoryId,
                    'catalog_id' => $catalogId,
                    'company_id' => $companyId,
                    'order' => $order,
                ]);
                $order++;
            }

            if ($defaultId > 0) {
                CatalogCategory::create([
                    'catalog_id' => $catalogId,
                    'category_id' => $defaultId,
                    'order' => $order,
                ]);
                OrderCategory::create([
                    'category_id' => $defaultId,
                    'catalog_id' => $catalogId,
                    'company_id' => $companyId,
                    'order' => $order,
                ]);
                $finalOrderIds[] = $defaultId;
            }

            $catalog->update(['cat_id' => implode(',', $finalOrderIds)]);
        });
    }

    /**
     * Reordena las categorías del catálogo FullVendor sin agregar ni quitar ninguna.
     *
     * @param list<int> $orderedCategoryIds IDs de categoría (sin la categoría por defecto) en el orden deseado.
     */
    public function reorderCategories(Catalog $catalog, array $orderedCategoryIds): void
    {
        if ((int) ($catalog->default_catalog ?? 0) !== 1) {
            return;
        }

        $companyId = (int) $catalog->company_id;
        $catalogId = (int) $catalog->catalog_id;

        $defaultCategory = Category::where('company_id', $companyId)
            ->where('category_default', 1)
            ->first();

        // Solo categorías activas (mismo criterio que GET catalog/{id}/categories y el modal).
        $existingActiveIds = CatalogCategory::query()
            ->where('catalog_category.catalog_id', $catalogId)
            ->join('categories', 'categories.cat_id', '=', 'catalog_category.category_id')
            ->where('categories.category_status', 1)
            ->when($defaultCategory, fn ($q) => $q->where('catalog_category.category_id', '!=', $defaultCategory->cat_id))
            ->orderBy('catalog_category.order')
            ->pluck('catalog_category.category_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $requestedIds = array_values(array_unique(array_map('intval', $orderedCategoryIds)));
        $requestedIds = array_values(array_filter($requestedIds, static fn ($id) => $id > 0));

        if ($requestedIds === []) {
            throw new \InvalidArgumentException('catalogo_default_reorder_only');
        }

        // Solo reordenar IDs que ya están en el catálogo (tras sync = categorías activas reales).
        // IDs stale del UI se ignoran (no tumbar Save/Apply).
        $existingSet = array_flip($existingActiveIds);
        $requestedIds = array_values(array_filter(
            $requestedIds,
            static fn ($id) => isset($existingSet[$id])
        ));
        if ($requestedIds === []) {
            throw new \InvalidArgumentException('catalogo_default_reorder_only');
        }

        // Resto de categorías activas no enviadas: se mantienen después, en su orden actual.
        $remainingIds = array_values(array_filter(
            $existingActiveIds,
            static fn ($id) => ! in_array($id, $requestedIds, true)
        ));

        // Categorías inactivas u otras no editables: conservarlas al final.
        $inactiveIds = CatalogCategory::query()
            ->where('catalog_id', $catalogId)
            ->when($defaultCategory, fn ($q) => $q->where('category_id', '!=', $defaultCategory->cat_id))
            ->whereNotIn('category_id', $existingActiveIds)
            ->orderBy('order')
            ->pluck('category_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $finalOrderIds = array_merge($requestedIds, $remainingIds, $inactiveIds);

        DB::transaction(function () use ($companyId, $catalogId, $finalOrderIds, $defaultCategory, $catalog) {
            // Fase 1: órdenes temporales (smallint unsigned máx 65535) para no chocar con unique_catalog_order.
            $tempOrder = 20000;
            foreach ($finalOrderIds as $categoryId) {
                CatalogCategory::where('catalog_id', $catalogId)
                    ->where('category_id', $categoryId)
                    ->update(['order' => $tempOrder]);

                OrderCategory::where('company_id', $companyId)
                    ->where('catalog_id', $catalogId)
                    ->where('category_id', $categoryId)
                    ->update(['order' => $tempOrder]);

                $tempOrder++;
            }

            if ($defaultCategory) {
                CatalogCategory::where('catalog_id', $catalogId)
                    ->where('category_id', $defaultCategory->cat_id)
                    ->update(['order' => $tempOrder]);

                OrderCategory::where('company_id', $companyId)
                    ->where('catalog_id', $catalogId)
                    ->where('category_id', $defaultCategory->cat_id)
                    ->update(['order' => $tempOrder]);
            }

            // Fase 2: órdenes finales según el arrastre del usuario (+ inactivas al final).
            $order = 1;
            foreach ($finalOrderIds as $categoryId) {
                CatalogCategory::where('catalog_id', $catalogId)
                    ->where('category_id', $categoryId)
                    ->update(['order' => $order]);

                OrderCategory::where('company_id', $companyId)
                    ->where('catalog_id', $catalogId)
                    ->where('category_id', $categoryId)
                    ->update(['order' => $order]);

                $order++;
            }

            if ($defaultCategory) {
                $defaultOrder = $order;
                CatalogCategory::where('catalog_id', $catalogId)
                    ->where('category_id', $defaultCategory->cat_id)
                    ->update(['order' => $defaultOrder]);

                OrderCategory::where('company_id', $companyId)
                    ->where('catalog_id', $catalogId)
                    ->where('category_id', $defaultCategory->cat_id)
                    ->update(['order' => $defaultOrder]);
            }

            $orderedCatId = $finalOrderIds;
            if ($defaultCategory) {
                $orderedCatId[] = (int) $defaultCategory->cat_id;
            }
            $catalog->update(['cat_id' => implode(',', $orderedCatId)]);
        });
    }

    /**
     * Categorías de la empresa en el orden del catálogo FullVendor (default_catalog=1).
     * La categoría por defecto va al final. Si faltan filas en order_categories, se sincroniza antes.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function categoriesInCatalogOrder(int $companyId): \Illuminate\Support\Collection
    {
        if ($companyId <= 0) {
            return collect();
        }

        $this->sync($companyId);

        $catalog = Catalog::where('company_id', $companyId)
            ->where('default_catalog', 1)
            ->first();

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->with(['imageRecord'])
            ->get();

        if (! $catalog) {
            return $categories
                ->sortBy([
                    fn (Category $c) => (int) ($c->category_default ?? 0) === 1 ? 1 : 0,
                    fn (Category $c) => (int) $c->cat_id,
                ])
                ->values();
        }

        $orderMap = OrderCategory::query()
            ->where('company_id', $companyId)
            ->where('catalog_id', (int) $catalog->catalog_id)
            ->pluck('order', 'category_id');

        return $categories
            ->sortBy(static function (Category $cat) use ($orderMap) {
                $isDefault = (int) ($cat->category_default ?? 0) === 1 ? 1 : 0;
                $order = (int) ($orderMap[$cat->cat_id] ?? 999999);

                return sprintf('%d-%06d-%09d', $isDefault, $order, (int) $cat->cat_id);
            })
            ->values();
    }

    /**
     * para que el listado/edición de catálogos muestre las categorías correctas.
     */
    protected function syncCatalogCategory(int $companyId, Catalog $catalog): void
    {
        $catalogId = (int) $catalog->catalog_id;

        $activeIds = Category::where('company_id', $companyId)
            ->where('category_status', 1)
            ->pluck('cat_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
        $activeSet = array_flip($activeIds);

        $orderRows = OrderCategory::where('company_id', $companyId)
            ->where('catalog_id', $catalogId)
            ->orderBy('order')
            ->get()
            ->filter(static fn ($row) => isset($activeSet[(int) $row->category_id]));

        if ($orderRows->isEmpty()) {
            CatalogCategory::withTrashed()->where('catalog_id', $catalogId)->forceDelete();
            return;
        }

        // Hard delete (incl. soft-deleted) para no chocar con unique_catalog_order.
        CatalogCategory::withTrashed()->where('catalog_id', $catalogId)->forceDelete();

        $order = 1;
        foreach ($orderRows as $row) {
            CatalogCategory::create([
                'catalog_id'  => $catalogId,
                'category_id' => (int) $row->category_id,
                'order'       => $order++,
            ]);
        }
    }
}
