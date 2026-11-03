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

    private bool $orderActivityRangeApplied = false;

    /**
     * El listado general de órdenes no incluye inventario (tipo_d = I).
     * Esas órdenes viven en su propio listado. Si el filtro pide tipo_d = I, no se excluyen.
     */
    public function setup(): void
    {
        $tipo = strtoupper(trim((string) $this->input('tipo_d', '')));
        if ($tipo === 'I') {
            return;
        }

        $this->where(function ($query) {
            $query->whereNull('orders.tipo_d')
                ->orWhereRaw("UPPER(TRIM(orders.tipo_d)) <> 'I'");
        });
    }

    public function company($companyId): OrderFilter
    {
        return $this->where('company_id', $companyId);
    }

    public function companyId($companyId): OrderFilter
    {
        return $this->company($companyId);
    }

    /**
     * Filtro explícito por tipo_d (D documento, C nota de crédito, I inventario).
     */
    public function tipoD($tipo): OrderFilter
    {
        $tipo = strtoupper(trim((string) $tipo));
        if ($tipo === '') {
            return $this;
        }

        return $this->whereRaw("UPPER(TRIM(orders.tipo_d)) = ?", [$tipo]);
    }

    /**
     * date_from / date_to: la orden entra si created o updated cae entero en el rango.
     * created es la fecha que muestra la grilla; updated es la última modificación.
     */
    public function dateFrom($from): OrderFilter
    {
        return $this->applyOrderActivityRange();
    }

    public function dateTo($to): OrderFilter
    {
        return $this->applyOrderActivityRange();
    }

    private function applyOrderActivityRange(): OrderFilter
    {
        if ($this->orderActivityRangeApplied) {
            return $this;
        }
        $this->orderActivityRangeApplied = true;

        $fromRaw = $this->input('date_from');
        $toRaw = $this->input('date_to');
        $from = is_string($fromRaw) && trim($fromRaw) !== '' ? Carbon::parse($fromRaw)->startOfDay() : null;
        $to = is_string($toRaw) && trim($toRaw) !== '' ? Carbon::parse($toRaw)->endOfDay() : null;
        if ($from === null && $to === null) {
            return $this;
        }

        return $this->where(function ($query) use ($from, $to) {
            $query->where(function ($created) use ($from, $to) {
                if ($from) {
                    $created->where('orders.created', '>=', $from);
                }
                if ($to) {
                    $created->where('orders.created', '<=', $to);
                }
            })->orWhere(function ($updated) use ($from, $to) {
                if ($from) {
                    $updated->where('orders.updated', '>=', $from);
                }
                if ($to) {
                    $updated->where('orders.updated', '<=', $to);
                }
            });
        });
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
