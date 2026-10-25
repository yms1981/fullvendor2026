<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . '/core/HasCatalog.php';

class Catalog extends Salesman_Controller
{
    use HasCatalog;
    protected $accessControlRole = 'seller';

    public function __construct()
    {
        parent::__construct();
        $this->benchmark->mark('view_class_creation');
        //$this->displayBenchmarks = true;
    }

    protected function getBenchmarkMessagesContent(){

        $this->addBenchmarkMessage('view_class_creation', 'show-content-end');
        $this->addBenchmarkMessage('view_class_creation', 'index_start');
        $this->addBenchmarkMessage('before_salesman_show_content', 'after_salesman_show_content','Salesman Show Content Preparation');
        $this->addBenchmarkMessage('before_salesman_data_for_view', 'after_salesman_data_for_view','Salesman data for view');
        $this->addBenchmarkMessage('application_get_data_for_view_start','application_get_data_for_view_end','Data for view');
        $this->addBenchmarkMessage('before_get_catalog_list', 'catalog_list_got','Catalog list From backend');
        $this->addBenchmarkMessage('show_catalog_start','show_catalog_end','Catalog prepare');
        $this->addBenchmarkMessage('load-view-layout-start', 'show-content-end');
        $this->addBenchmarkMessage('my-controller-construct', 'show-content-end');
        return parent::getBenchmarkMessagesContent();
    }


    public function index() {
        if($this->getCustomerId() === null)
            $this->redirectToApplication($this->getCompany());
        $this->benchmark->mark('index_start');
        $this->showCatalog();
    }
}
