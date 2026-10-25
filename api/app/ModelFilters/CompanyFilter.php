<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CompanyFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function searchText($value)
    {
        $this->name($value);
        $this->shortName($value);
        $this->email($value);
        return $this;
    }

    public function name($name)
    {
        return $this->orWhere('name', 'LIKE', "%$name%");
    }

    public function shortName($shortName)
    {
        return $this->orWhere('short_name', 'LIKE', "%$shortName%");
    }

    public function email($email)
    {
        return $this->orWhere('email', 'LIKE', "%$email%");
    }
    public function id($id)
    {
        return $this->where('company_id',$id);
    }

    /**
     * Compañías raíz (sin compañía padre): createdby_comp_id NULL o 0.
     * Query param: rootOnly=1 (listado admin MVC vía API).
     */
    public function rootOnly($value)
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0 || $value === false) {
            return $this;
        }

        return $this->where(static function ($q): void {
            $q->whereNull('createdby_comp_id')->orWhere('createdby_comp_id', 0);
        });
    }

    /**
     * Empresas asignadas a una categoría (company_category_id o category_id legado).
     * Query param: companyCategoryId={id}
     */
    public function companyCategoryId($value)
    {
        $categoryId = (int) $value;
        if ($categoryId < 1) {
            return $this;
        }

        return $this->where(static function ($q) use ($categoryId): void {
            $q->where('company_category_id', $categoryId);
            if (\Illuminate\Support\Facades\Schema::hasColumn('companies', 'category_id')) {
                $q->orWhere('category_id', $categoryId);
            }
        });
    }

    /**
     * Empresas hijas creadas por otra compañía (createdby_comp_id).
     */
    public function createdByCompId($value)
    {
        $parentId = (int) $value;
        if ($parentId < 1) {
            return $this;
        }

        return $this->where('createdby_comp_id', $parentId);
    }

    /**
     * Sin stripe_id (sincronización masiva pendiente en admin).
     */
    public function stripePending($value)
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0 || $value === false) {
            return $this;
        }
        if (! \Illuminate\Support\Facades\Schema::hasColumn('companies', 'stripe_id')) {
            return $this;
        }

        return $this->where(static function ($q): void {
            $q->whereNull('stripe_id')->orWhere('stripe_id', '');
        });
    }

}
