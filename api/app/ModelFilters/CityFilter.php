<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CityFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function name($name)
    {
        return $this->orWhere('name', 'LIKE', "$name%");
    }

    public function state_id($stateId)
    {
        if ($stateId === null || $stateId === '' || $stateId === 'todo' || $stateId == -1) {
            return $this;
        }
        return $this->where('state_id', (int) $stateId);
    }
}
