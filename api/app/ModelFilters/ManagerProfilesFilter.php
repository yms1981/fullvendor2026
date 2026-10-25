<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class ManagerProfilesFilter extends ModelFilter
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

    public function status($status)
    {
        return $this->where('is_active', $status);
    }
}
