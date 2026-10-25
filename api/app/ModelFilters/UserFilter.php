<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class UserFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function search($term)
    {
        return $this->where(function ($query) use ($term) {
            $query->where('name', 'LIKE', "%{$term}%")
                ->orWhere('first_name', 'LIKE', "%{$term}%")
                ->orWhere('last_name', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%");
        });
    }

    public function type($type)
    {
        $companyId = request()->input('company_id');
        
        return $this->whereHas('companyUsers', function ($query) use ($type, $companyId) {
            $query->where('type', $type);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        });
    }

    public function status($status)
    {
        if ($status === 'active') {
            return $this->whereNull('deleted_at');
        } elseif ($status === 'inactive') {
            return $this->whereNotNull('deleted_at');
        }
        
        return $this;
    }
}
