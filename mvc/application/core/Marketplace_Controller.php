<?php

class Marketplace_Controller extends Application_Controller
{

    protected string $layout = 'marketplace';

    public function __construct()
    {
        parent::__construct();
        $this->addJsFile('/companyasset/js/jquery-3.5.1.js');
        $this->addJsFile('/companyasset/antomi/assets/js/plugins.js');
        $this->addJsFile('/companyasset/antomi/assets/js/sweetalert2.all.min.js');
    }

    public function showContent($contentView, $data = [], $layout = 'marketplace')
    {
        $data['base_url'] = marketplace_url('catalog');
        $data['company'] = $this->getObjectFromApi('company/'.$this->getCompany());
        $data['user'] = $this->getUser();
        $data['user']['role'] = $this->getRole();
        $data['lang_json'] = $this->getDictionary();
        parent::showContent($contentView, $data, $layout);
    }
}
