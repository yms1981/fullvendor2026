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
        $trimmedTerms = array_map('trim', $termList);
        // Convertir términos a mayúsculas para la búsqueda
        $trimmedTerms = array_map(function($term) {
            return mb_strtoupper($term, 'UTF-8');
        }, $trimmedTerms);
        $this->where(function ($qTerms) use ($trimmedTerms) {
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->orWhere('sku', 'like', '%' . $term . '%');
                    $q->orWhere('product_id', $term);
                }
            });
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->where('name', 'like', '%' . $term . '%');
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



}
