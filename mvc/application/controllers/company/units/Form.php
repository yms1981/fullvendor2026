<?php

class Form  extends Company_Controller
{
    use IsCRUD;
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
    }

    protected function getDataForView($view=null,$dataForView=[]) {
        $dataForView = parent::getDataForView($view,$dataForView);

        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $pageTexts + $this->getModuleLang('unit-languaje');
        $dataForView['pageTexts'] =  $pageTexts;

        return $dataForView;
    }
}
