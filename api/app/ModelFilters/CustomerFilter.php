<?php

namespace App\ModelFilters;

use App\Models\Vendor;
use EloquentFilter\ModelFilter;

class CustomerFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];
    protected string $orderColumn = 'customer_id';

    public function seller($sellerId)
    {
        if ($sellerId == -1)
            return $this->withoutSeller();
        if ($sellerId === null || $sellerId === '' || $sellerId === 'todo')
            return $this;

        return $this->whereHas('sellers', function ($query) use ($sellerId) {
            $query->where('customer_seller.seller_id', $sellerId);
        });
    }
    public function textToSearch($searchTerm)
    {
        return $this->where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('business_name', 'like', "%{$searchTerm}%");
        });
    }

    public function company($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function companyId($companyId)
    {
        return $this->company($companyId);
    }

    public function group($groupId)
    {
        if ($groupId == -1)
            return $this->where(function ($query) {
                $query->whereNull('group_id')
                    ->orWhere('group_id', 0); // actualmente hay muchos con 0
            });
        if ($groupId === null || $groupId === '' || $groupId === 'todo')
            return $this;

        return $this->where('group_id', $groupId);
    }

    public function status($status)
    {
        if ($status == 'all' || $status === null || $status === '' || $status === 'todo')
            return $this;

        return $this->where('customer_status', $status);
    }

    public function catalogEmails($catalogEmail)
    {
        return $this->where('catalog_emails', $catalogEmail);
    }

    public function term($termId)
    {
        if ($termId == -1)
            return $this->whereNull('term_id');
        if ($termId === null || $termId === '' || $termId === 'todo')
            return $this;

        return $this->where('term_id', $termId);
    }

    public function country($countryId)
    {
        if ($countryId == -1 || $countryId == 'todo')
            return $this;

        // Buscar el nombre del país por su ID
        $country = \App\Models\Country::find($countryId);
        if ($country) {
            return $this->where('commercial_country', $country->name);
        }

        return $this;
    }

    public function state($stateId)
    {
        if ($stateId == -1 || $stateId == 'todo')
            return $this;

        // Buscar el nombre del estado por su ID
        $state = \App\Models\State::find($stateId);
        if ($state) {
            return $this->where('commercial_state', $state->name);
        }

        return $this;
    }

    public function search($search)
    {
        if ($search === null || $search === '')
            return $this;

        return $this->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('business_name', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}")
                ->orWhere('email', 'like', "{$search}%")
//                ->orWhere('commercial_state', 'like', "{$search}%")
                ->orWhere('customer_id', 'like', "%{$search}");
        });
    }

    public function user($userId)
    {
        return $this->where('user_id', $userId);
    }

    public function orderSort($orderSort = 'customer_id')
    {
        $this->orderColumn = $orderSort;
        return $this->orderBy($orderSort);
    }

    public function orderType($orderType = 'DESC')
    {
        return $this->orderBy($this->orderColumn, $orderType);
    }
}
