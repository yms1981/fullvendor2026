<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CustomerGroupFilter extends ModelFilter
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

    public function companyId($companyId)
    {
        return $this->company($companyId);
    }

    public function status($status)
    {
        return $this->where('group_status', $status);
    }

    public function terms($termList)
    {
        $trimmedTerms = array_map('trim', $termList);
        $this->where(function ($qTerms) use ($trimmedTerms) {
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->orWhere('group_id', $term);
                }
            });
            $qTerms->orWhere(function ($q) use ($trimmedTerms) {
                foreach ($trimmedTerms as $term) {
                    $q->where('name', 'like', '%' . $term . '%');
                }
            });
        });
    }
}
