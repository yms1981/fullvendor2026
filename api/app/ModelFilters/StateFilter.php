<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class StateFilter extends ModelFilter
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

    public function country_id($countryId)
    {
        if ($countryId === null || $countryId === '' || $countryId === 'todo' || $countryId == -1) {
            return $this;
        }
        return $this->where('country_id', (int) $countryId);
    }
}
