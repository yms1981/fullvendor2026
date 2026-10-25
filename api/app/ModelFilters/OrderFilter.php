<?php

namespace App\ModelFilters;

use App\Models\StatusOrder;
use Carbon\Carbon;
use EloquentFilter\ModelFilter;

class OrderFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function company($companyId): OrderFilter
    {
        return $this->where('company_id', $companyId);
    }

    public function companyId($companyId): OrderFilter
    {
        return $this->company($companyId);
    }

    /** Filtro desde fecha (date_from): órdenes desde este día (inclusivo) */
    public function dateFrom($from): OrderFilter
    {
        $date = Carbon::parse($from)->startOfDay();
        return $this->whereRaw('IFNULL(orders.updated, orders.created) >= ?', [$date]);
    }

    /** Filtro hasta fecha (date_to): órdenes hasta este día (inclusivo) */
    public function dateTo($to): OrderFilter
    {
        $date = Carbon::parse($to)->endOfDay();
        return $this->whereRaw('IFNULL(orders.updated, orders.created) <= ?', [$date]);
    }

    public function seller($sellers): OrderFilter
    {
        $ids = is_array($sellers) ? $sellers : [$sellers];
        return $this->whereIn('seller_id', $ids);
    }

    public function source($source): OrderFilter
    {
        if ($source === 'todo' || $source === '' || $source === null) {
            return $this;
        }
        $normalized = strtolower((string) $source);
        // Catálogo / Web (incluye valores legacy "Catalog").
        if (in_array($normalized, ['catalog', 'web', 'catalogo', 'company_panel'], true)) {
            return $this->whereRaw(
                'LOWER(source) IN (?, ?, ?, ?)',
                ['catalog', 'web', 'catalogo', 'company_panel']
            );
        }
        if ($normalized === 'app') {
            return $this->whereRaw('LOWER(source) IN (?, ?, ?, ?)', ['app', 'mobile', 'android', 'ios']);
        }
        if ($normalized === 'manual') {
            return $this->whereRaw('LOWER(source) IN (?, ?)', ['manual', 'erp_import']);
        }

        return $this->whereRaw('LOWER(source) = ?', [$normalized]);
    }

    /**
     * Filtro por estado. Se enlaza por cod_status: se resuelve a id_status para la BD.
     * @param int|int[] $status cod_status o lista de cod_status
     */
    public function status($status): OrderFilter
    {
        $codStatuses = is_array($status) ? $status : [$status];
        $codStatuses = array_map('intval', $codStatuses);
        $idStatuses = StatusOrder::whereIn('cod_status', $codStatuses)->pluck('id_status')->toArray();
        if (empty($idStatuses)) {
            return $this->whereRaw('1 = 0');
        }
        return $this->whereIn('status_id', $idStatuses);
    }

    public function group($group): OrderFilter
    {
        if ($group === 'todo' || $group === '' || $group === null || $group === '-1') {
            return $this;
        }
        return $this->whereHas('customer', function ($q) use ($group) {
            $q->where('group_id', $group);
        });
    }

    public function catalog($catalog): OrderFilter
    {
        if ($catalog === 'todo' || $catalog === '' || $catalog === null) {
            return $this;
        }
        return $this->where('catalog_id', $catalog);
    }

    public function search($search): OrderFilter
    {
        if ($search === '' || $search === null) {
            return $this;
        }
        return $this->where(function ($query) use ($search) {
            $query->where('order_number', 'LIKE', "%{$search}%")
                ->orWhereHas('customer', function ($q) use ($search) {
                    $q->where('business_name', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                });
        });
    }
}
