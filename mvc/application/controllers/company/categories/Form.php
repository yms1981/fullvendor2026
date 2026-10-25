<?php

class Form extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);

        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $pageTexts + $this->langArr;
        $dataForView['pageTexts'] = $pageTexts;

        return $dataForView;
    }
}
