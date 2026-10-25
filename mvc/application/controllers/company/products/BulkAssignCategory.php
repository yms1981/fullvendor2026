<?php

class BulkAssignCategory extends Company_Controller
{

    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
        $this->langArr = $this->getModuleLang('cmp-products');
    }

    public function run()
    {
        echo $this->bulkRelate(
            (object)$this->input->post(),
            'categories_toasigned',
            'category',
            'products',
            'category_name',
            'must_select_category',
            'category_asigned',
            'bnProdCat'
        );
    }

}
