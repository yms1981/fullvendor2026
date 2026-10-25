<?php

class Allcategories extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
    }

    public function index() {
        $categories = $this->getArrayFromApi("company/{$this->getCompanyId()}/categories");
        return $this->Helper->response_json($categories);
    }
}
