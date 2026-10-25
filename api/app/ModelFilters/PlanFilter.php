<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PlanFilter extends ModelFilter
{
    public $relations = [];

    public function status($status)
    {
        return $this->where('status', (int) $status);
    }
}
