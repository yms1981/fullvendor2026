<?php

class BulkAddToCatalog extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
        $this->langArr = $this->getModuleLang('cmp-products');
    }

    public function run() {
        echo $this->bulkRelate(
            (object)$this->input->post(),
            'catalogs',
            'catalog',
            'products',
            'catalog_name',
            'must_select_catalog',
            'added_tocatalog',
            'catalogs'
        );
    }
}
