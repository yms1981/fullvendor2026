<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function company($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function search($term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $this;
        }

        return $this->where(function ($query) use ($term) {
            $query->where('name', 'like', '%'.$term.'%')
                ->orWhere('sku', 'like', '%'.$term.'%')
                ->orWhere('barcode', 'like', '%'.$term.'%')
                ->orWhere('tags', 'like', '%'.$term.'%')
                ->orWhere('descriptions', 'like', '%'.$term.'%');
        });
    }

    /**
     * @param int $status 0 DISABLED, 1 ENABLED, 3 ALL
     * @return ProductFilter
     */
    public function prdstatus($status)
    {
        if ($status == 3) return $this;

        return $this->where('status', $status);
    }

    /**
     * @param int $category categoryId or -1 to get the ones without categories
     * @return ProductFilter
     */
    public function category($category)
    {
        if ($category == -1) {
            return $this->withoutCategories();
        }

        return $this->whereHas('categories', function ($query) use ($category) {
            $query->where('cat_id', $category);
        });
    }

    /**
     * Filters products with or without photo
     *
     * @param $withOrWithout
     * @return $this
     */
    public function photo($withOrWithout) {
        if($withOrWithout == 'whith_photo') {
            return $this->withPhoto();
        }
        elseif($withOrWithout == 'whithout_photo') {
            return $this->withoutPhoto();
        }
        return $this;
    }

    public function tag($tagOrTags)
    {
        $tags = explode(',', $tagOrTags);
        $trimmedTags = array_map('trim', $tags);
        $this->where(function ($q) use ($trimmedTags) {
            foreach ($trimmedTags as $tag) {
                $q->orWhere('tags','like', '%'.$tag.'%');
            }
        });
    }

    public function location($location)
    {
        if (!empty($location)) {
            $this->where('locations', 'like', '%' . $location . '%');
        }
    }

    public function terms($termList)
    {
        if (! is_array($termList)) {
            $termList = preg_split('/\s+/', (string) $termList) ?: [];
        }

        $rawTerms = [];
        foreach ($termList as $term) {
            $term = trim((string) $term);
            if ($term !== '') {
                $rawTerms[] = $term;
            }
        }
        if ($rawTerms === []) {
            return $this;
        }

        // Mayúsculas para SKU/nombre; barcode se busca también con el valor original.
        $upperTerms = array_map(static function ($term) {
            return mb_strtoupper($term, 'UTF-8');
        }, $rawTerms);

        return $this->where(function ($qTerms) use ($rawTerms, $upperTerms) {
            $qTerms->orWhere(function ($q) use ($rawTerms, $upperTerms) {
                foreach ($upperTerms as $i => $term) {
                    $q->orWhere('sku', 'like', '%'.$term.'%');
                    $q->orWhere('product_id', $term);
                    $q->orWhere('pro_id', $term);
                    // Código de barras (uno o varios en el mismo campo).
                    $q->orWhere('barcode', 'like', '%'.$term.'%');
                    $original = $rawTerms[$i] ?? $term;
                    if ($original !== $term) {
                        $q->orWhere('barcode', 'like', '%'.$original.'%');
                    }
                }
            });
            $qTerms->orWhere(function ($q) use ($upperTerms) {
                foreach ($upperTerms as $term) {
                    $q->where('name', 'like', '%'.$term.'%');
                }
            });
        });
    }

    public function catalog($catalogId) {
        $this->where(function ($query) use ($catalogId) {
            $query->whereHas('catalogs', function ($q) use ($catalogId) {
                $q->where('catalog_product.catalog_id', $catalogId);
            })->orWhereHas('categories', function ($q) use ($catalogId) {
                $q->whereHas('catalogs', function ($q) use ($catalogId) {
                    $q->where('catalog_category.catalog_id', $catalogId);
                });
            });
        });
    }

    /**
     * Filtro de avisos de calidad: duplicate_barcode | zero_price | stock_fixed.
     */
    public function quality($type)
    {
        $type = strtolower(trim((string) $type));
        if ($type === '') {
            return $this;
        }

        $companyId = (int) ($this->input('company') ?? $this->input('company_id') ?? 0);
        if ($companyId < 1) {
            return $this->whereRaw('1 = 0');
        }

        if ($type === \App\Services\ProductListQualityService::QUALITY_ZERO_PRICE) {
            return $this->where('sale_price', '=', 0);
        }

        $ids = app(\App\Services\ProductListQualityService::class)
            ->productIdsForFilter($companyId, $type);

        if ($ids === []) {
            return $this->whereRaw('1 = 0');
        }

        return $this->whereIn('pro_id', $ids);
    }
}
