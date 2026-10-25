<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class InvoiceFilter extends ModelFilter
{
    public $relations = [];

    public function company($companyId)
    {
        return $this->where('company_id', (int) $companyId);
    }

    public function subscription($subscriptionId)
    {
        return $this->where('subscription_id', (int) $subscriptionId);
    }

    public function entidadId($entidadId)
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('invoices', 'entidad_id')) {
            return $this;
        }

        return $this->where('entidad_id', (int) $entidadId);
    }
}
