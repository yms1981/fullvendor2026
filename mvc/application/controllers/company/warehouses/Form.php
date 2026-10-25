<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Form extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('warehouse', 'company/warehouse/', 'warehouses');
        $this->langArr = $this->getModuleLang('cmp-warehouse');
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);

        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $pageTexts + (is_array($this->langArr) ? $this->langArr : []);
        $dataForView['pageTexts'] = $pageTexts;

        return $dataForView;
    }

    /**
     * País por defecto desde companies.country (ID numérico, ISO o nombre).
     *
     * @param object|array|null $company
     * @param array $countries
     */
    protected function resolveCompanyDefaultCountryId($company, array $countries): ?int
    {
        if (empty($company) || empty($countries)) {
            return null;
        }

        $raw = trim((string) (is_object($company) ? ($company->country ?? '') : ($company['country'] ?? '')));
        if ($raw === '') {
            return null;
        }

        $countryIds = [];
        foreach ($countries as $c) {
            $id = (int) (is_object($c) ? ($c->id ?? $c->country_id ?? 0) : ($c['id'] ?? $c['country_id'] ?? 0));
            if ($id > 0) {
                $countryIds[$id] = true;
            }
        }

        if (ctype_digit($raw)) {
            $id = (int) $raw;
            if (isset($countryIds[$id])) {
                return $id;
            }
        }

        $normalize = static function ($value) {
            $value = (string) $value;
            if (function_exists('iconv')) {
                $tr = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
                if ($tr !== false) {
                    $value = $tr;
                }
            }

            return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
        };

        $needle = $normalize($raw);
        $needleSortname = strtoupper($raw);

        foreach ($countries as $c) {
            $candidateId = (int) (is_object($c) ? ($c->id ?? $c->country_id ?? 0) : ($c['id'] ?? $c['country_id'] ?? 0));
            if ($candidateId <= 0) {
                continue;
            }
            $sortname = is_object($c) ? ($c->sortname ?? '') : ($c['sortname'] ?? '');
            $name = is_object($c) ? ($c->name ?? '') : ($c['name'] ?? '');
            if ($sortname !== '' && strcasecmp(trim((string) $sortname), $needleSortname) === 0) {
                return $candidateId;
            }
            if ($name !== '' && $normalize($name) === $needle) {
                return $candidateId;
            }
        }

        return null;
    }
}
