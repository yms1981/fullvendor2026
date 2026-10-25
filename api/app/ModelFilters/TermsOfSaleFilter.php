<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class TermsOfSaleFilter extends ModelFilter
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

    public function status($status)
    {
        return $this->where('term_status', $status);
    }

    /**
     * Búsqueda por nombre o term_id (términos separados por espacio, OR entre términos).
     */
    public function terms($termList)
    {
        if (!is_array($termList)) {
            $termList = array_filter(array_map('trim', explode(' ', (string) $termList)));
        }
        if (empty($termList)) {
            return $this;
        }
        return $this->where(function ($query) use ($termList) {
            foreach ($termList as $term) {
                if ($term === '') {
                    continue;
                }
                $query->orWhere(function ($q) use ($term) {
                    $q->where('name', 'like', '%' . $term . '%')
                        ->orWhere('term_id', 'like', '%' . $term . '%');
                });
            }
        });
    }
}
