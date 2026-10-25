<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class UnitFilter extends ModelFilter
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

    public function terms($termList)
    {
        $trimmedTerms = array_map('trim', $termList);
        $this->where(function ($qTerms) use ($trimmedTerms) {
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->orWhere('unit_id', $term);
                }
            });
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->where('unit', 'like', '%' . $term . '%');
                }
            });
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->where('description', 'like', '%' . $term . '%');
                }
            });
        });
    }
}
