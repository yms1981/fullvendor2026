<?php

class Form extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('vendor', 'company/seller/', 'sellers');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    protected function getDataForView($view=null,$dataForView=[]) {
        $dataForView = parent::getDataForView($view,$dataForView);

        $pageTexts = array_merge(
            $this->getModuleLang('unit-languaje'),
            $this->getModuleLang('perfil-vendedores'),
            $this->getModuleLang('cmp-vendedor'),
            $dataForView['pageTexts'] ?? [],
            is_array($this->langArr) ? $this->langArr : []
        );
        $dataForView['pageTexts'] = $pageTexts;

        return $dataForView;
    }
}
