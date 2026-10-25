<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class LogFilter extends ModelFilter
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
        return $this->where('company_id', (int) $companyId);
    }

    public function fromDate($fromDate)
    {
        return $this->whereDate('created_on', '>=', $fromDate);
    }

    public function toDate($toDate)
    {
        return $this->whereDate('created_on', '<=', $toDate);
    }

    public function logType($logType)
    {
        return $this->where('log_type', $logType);
    }
}
