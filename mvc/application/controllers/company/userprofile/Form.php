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
        $this->initCrud('sellerprofiles', 'company/userprofile/', 'userprofiles');
        $this->langArr = $this->getModuleLang('cmp-profile-vendor');
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);

        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $pageTexts + (is_array($this->langArr) ? $this->langArr : []);
        $pageTexts = $pageTexts + $this->getModuleLang('cmp-profile');
        $dataForView['pageTexts'] = $pageTexts;

        return $dataForView;
    }
}

