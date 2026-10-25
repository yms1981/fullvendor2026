<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class SubscriptionFilter extends ModelFilter
{
    public $relations = [];

    public function company($companyId)
    {
        return $this->where('company_id', (int) $companyId);
    }

    public function planId($planId)
    {
        return $this->where('plan_id', (int) $planId);
    }

    public function status($status)
    {
        return $this->where('status', (int) $status);
    }
}
