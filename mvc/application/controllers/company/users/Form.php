<?php

class Form  extends Company_Controller
{
    use IsCRUD;
    use ConfigCrudListTools;
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('user', 'company/user/', 'users');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    protected function initListButtonBar(): void
    {
        $this->listButtonBar = new ButtonBar();
        $tabType = $this->input->get('type');
        if (! is_string($tabType) || ! in_array($tabType, ['manager', 'customer', 'seller', 'warehouse'], true)) {
            $tabType = 'manager';
        }
        $this->listButtonBar->addModalButton(
            $this->langArr['add_user'] ?? ($this->langArr['btn_new'] ?? ''),
            '#modalAddUser',
            null,
            'mdi mdi-plus-circle',
            ['default-role' => $tabType]
        );
    }

    protected function getDataForView($view=null,$dataForView=[]) {
        $dataForView = parent::getDataForView($view,$dataForView);

        $pageTexts = array_merge(
            $this->getModuleLang('unit-languaje'),
            $this->getModuleLang('perfil-vendedores'),
            $this->getModuleLang('cmp-vendedor'),
            $this->getModuleLang('cmp-clientes'),
            $dataForView['pageTexts'] ?? [],
            is_array($this->langArr) ? $this->langArr : []
        );
        $dataForView['pageTexts'] = $pageTexts;
        $this->fillEmptyUserFormLabels($dataForView['pageTexts']);

        $this->injectCompanyUserPhoneCountryMeta($dataForView);

        return $dataForView;
    }

    /**
     * Si el módulo en BD trae claves vacías, completar desde el bloque "cmp-user" en api/lang/{es-ES|en-EN}.json.
     */
    protected function fillEmptyUserFormLabels(array &$pageTexts): void
    {
        $fileLang = $this->getLangJsonSection('cmp-user');
        if ($fileLang === []) {
            return;
        }
        foreach ($fileLang as $key => $value) {
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }
            $cur = isset($pageTexts[$key]) ? trim((string) $pageTexts[$key]) : '';
            if ($cur === '') {
                $pageTexts[$key] = (string) $value;
            }
        }
        if (trim((string) ($pageTexts['ok'] ?? '')) === '') {
            $common = $this->getLangJsonSection('cmp-common');
            if (isset($common['ok'])) {
                $pageTexts['ok'] = (string) $common['ok'];
            }
        }

        $tel = trim((string) ($pageTexts['telefono'] ?? ''));
        if ($tel === '') {
            $pageTexts['telefono'] = trim((string) ($pageTexts['col_phone'] ?? $pageTexts['phones'] ?? $pageTexts['phone_number'] ?? ''));
        }
        $cell = trim((string) ($pageTexts['cell_number'] ?? ''));
        if ($cell === '') {
            $pageTexts['cell_number'] = trim((string) ($pageTexts['col_cellphone'] ?? $pageTexts['cell_phone'] ?? ''));
        }
    }

    /**
     * Metadatos del país de la compañía para máscaras de teléfono (misma lógica que clientes).
     */
    protected function injectCompanyUserPhoneCountryMeta(array &$dataForView): void
    {
        $meta = [
            'id' => '',
            'sortname' => '',
            'phonecode' => 0,
            'phonemask' => '',
            'name' => '',
        ];
        $company = $dataForView['company'] ?? null;
        if (!$company && $this->getCompanyId()) {
            $company = $this->getObjectFromApi('company/' . $this->getCompanyId());
        }
        $countryId = null;
        if (is_object($company) && isset($company->country)) {
            $countryId = $company->country;
        }
        if ($countryId !== null && $countryId !== '') {
            $c = $this->getObjectFromApi('country/' . (int) $countryId, [
                'props' => ['id', 'sortname', 'phonecode', 'phonemask', 'name'],
            ]);
            if (!$this->hasError($c) && is_object($c)) {
                $meta['id'] = (string) ($c->id ?? '');
                $meta['sortname'] = strtolower((string) ($c->sortname ?? ''));
                $meta['phonecode'] = (int) ($c->phonecode ?? 0);
                $meta['phonemask'] = (string) ($c->phonemask ?? '');
                $meta['name'] = (string) ($c->name ?? '');
            }
        }
        $dataForView['companyPhoneCountryMeta'] = $meta;
        $dataForView['companyUserCountryFlagsUrl'] = rtrim((string) base_url(), '/') . '/images/flags/';
    }
}
