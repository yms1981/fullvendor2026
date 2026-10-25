<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CountryFilter extends ModelFilter
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

    /**
     * Search by name or sortname (country code). Accepts one term; matches containing the value.
     */
    public function search($value)
    {
        $term = trim((string) $value);
        if ($term === '') {
            return $this;
        }
        $like = '%' . $term . '%';
        return $this->where(function ($query) use ($like) {
            $query->where('name', 'LIKE', $like)
                ->orWhere('sortname', 'LIKE', $like);
        });
    }
}
