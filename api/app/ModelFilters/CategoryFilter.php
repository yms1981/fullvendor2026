<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CategoryFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];
    protected string $orderColumn = 'cat_id';

    public function language($languageId)
    {
        return $this->where('language_id', $languageId);
    }

    public function status($status)
    {
        return $this->where('category_status', $status);
    }

    public function company($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function categoryStatus($status)
    {
        return $this->where('category_status', $status);
    }

    public function terms($termList)
    {
        $trimmedTerms = array_map('trim', $termList);
        $this->where(function ($qTerms) use ($trimmedTerms) {
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->orWhere('cat_id', $term);
                }
            });
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->where('category_name', 'like', '%' . $term . '%');
                }
            });
        });
    }

    public function orderSort($orderSort = 'cat_id')
    {
        $this->orderColumn = $orderSort;
        return $this->orderBy($orderSort);
    }

    public function orderType($orderType = 'DESC')
    {
        return $this->orderBy($this->orderColumn, $orderType);
    }

    public function ids(array $ids)
    {
        if (empty($ids)) {
            return $this;
        }

        return $this->whereIn('cat_id', $ids);
    }
}
