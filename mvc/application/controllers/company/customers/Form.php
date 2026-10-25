<?php

class Form extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customer', 'company/customer/', 'customers');
        $this->langArr = $this->getModuleLang('cmp-cliente');
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);

        $lang = $this->getModuleLang('cmp-cliente');
        if (! is_array($lang)) {
            $lang = is_object($lang) ? (array) $lang : [];
        }

        // Si el JSON local no está montado o el diccionario de sesión está desfasado,
        // pedir el bloque fresco a langmdl (API) para claves nuevas.
        if (! isset($lang['emails_add']) || trim((string) $lang['emails_add']) === '') {
            if (! isset($this->langmodel)) {
                $this->load->model('langmodel');
            }
            if (isset($this->langmodel) && method_exists($this->langmodel, 'getMdl')) {
                $langId = method_exists($this, 'resolveCompanyPanelLangId')
                    ? (int) $this->resolveCompanyPanelLangId()
                    : 2;
                $fresh = $this->langmodel->getMdl('cmp-cliente', $langId);
                if (is_object($fresh)) {
                    $fresh = json_decode(json_encode($fresh), true);
                }
                if (is_array($fresh) && $fresh !== []) {
                    $lang = array_merge($lang, $fresh);
                }
            }
        }

        $this->langArr = $lang;

        $pageTexts = array_merge(
            $dataForView['pageTexts'] ?? [],
            $lang
        );
        $dataForView['pageTexts'] = $pageTexts;

        return $dataForView;
    }

    /**
     * País por defecto desde configuración de la empresa (companies.country).
     * En perfil/compañía se guarda el ID del país (valor del &lt;select name="country"&gt;), no el nombre.
     *
     * @param object|array|null $company
     * @param array $countries listado de países de la API
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

        // companies.country = ID (configuración en /company/profile)
        if (ctype_digit($raw)) {
            $id = (int) $raw;
            if (isset($countryIds[$id])) {
                return $id;
            }
        }

        $normalize = function ($value) {
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
