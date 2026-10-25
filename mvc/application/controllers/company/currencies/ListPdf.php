<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ListPdf extends Company_Controller
{
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
        $this->langArr = $this->getModuleLang('cm-currency');
    }

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $langUnit = $this->getModuleLang('unit-languaje');
        $title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'titulo_list');
        $companyId = (int) $this->getCompanyId();
        $companyCurrency = $this->getObjectFromApi("company/{$companyId}/currency");

        $headers = [
            $langUnit['continent'] ?? '',
            $langUnit['country'] ?? '',
            $langUnit['currency'] ?? '',
            $langUnit['code'] ?? '',
            $langUnit['symbol'] ?? '',
        ];

        $rows = [];
        if (!$this->hasError($companyCurrency) && is_object($companyCurrency)) {
            $tags = is_object($companyCurrency->tags ?? null) ? (array) $companyCurrency->tags : (array) ($companyCurrency->tags ?? []);
            $rows[] = [
                (string) ($tags['main_category'] ?? $companyCurrency->GroupID ?? ''),
                (string) ($tags['country_category'] ?? $companyCurrency->Contry ?? ''),
                (string) ($companyCurrency->name ?? $companyCurrency->Name ?? ''),
                (string) ($companyCurrency->code ?? $companyCurrency->Code ?? ''),
                (string) ($companyCurrency->symbol ?? $companyCurrency->Symbol ?? ''),
            ];
        }

        $this->sendConfigListPdf($headers, $rows, $title !== '' ? $title : 'currency', 'currency_list');
    }
}
