<?php

class BulkRemoveCategory extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    public function index() {
        echo $this->bulkUnrelate(
            (object)$this->input->post(),
            'categories',
            'category',
            'products',
            'category_name',
            'must_select_category',
            'category_removed',
            'bnProdCat'
        );
    }
}
