<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class RolsFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function type($type)
    {
        return $this->where('type', 'like', "%{$type}%");
    }

    public function desc_es($descEs)
    {
        return $this->where('desc_es', 'like', "%{$descEs}%");
    }

    public function desc_en($descEn)
    {
        return $this->where('desc_en', 'like', "%{$descEn}%");
    }
}

