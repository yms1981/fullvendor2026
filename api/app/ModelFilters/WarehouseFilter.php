<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;
use Illuminate\Support\Facades\DB;

class WarehouseFilter extends ModelFilter
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

    public function name($name)
    {
        if ($name === null || $name === '') {
            return $this;
        }

        return $this->where('name', 'like', '%'.$name.'%');
    }
}
