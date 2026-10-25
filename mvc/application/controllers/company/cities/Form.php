<?php

class Form  extends Company_Controller
{
    use IsCRUD;
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('city', 'company/cities', 'cities');
    }

    protected function getDataForView($view=null,$dataForView=[]) {
        $dataForView = parent::getDataForView($view,$dataForView);

        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $pageTexts + $this->getModuleLang('cmp-terms');
        $dataForView['pageTexts'] =  $pageTexts;

        return $dataForView;
    }
}
