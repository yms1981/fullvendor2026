<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class VendorFilter extends ModelFilter
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
        return $this->where('status', $status);
    }

    public function limit($limit){
        return $this->take($limit);
    }

    public function userId($userId)
    {
        return $this->where('user_id', $userId);
    }

    public function user($userId)
    {
        return $this->userId($userId);
    }
}
