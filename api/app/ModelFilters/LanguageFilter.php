<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class LanguageFilter extends ModelFilter
{
    public $relations = [];

    public function status($status)
    {
        return $this->where('status', (int) $status);
    }
}
