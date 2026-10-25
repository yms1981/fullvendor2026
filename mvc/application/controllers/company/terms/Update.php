<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    function __construct()
    {
        parent::__construct();
        $this->initCrud('termsofsale', 'company/term/', 'terms');
        $this->langArr = $this->getModuleLang('cmp-terms');
    }

    function run($termId=null)
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();

        $response = $this->put("{$this->entityPath}/{$termId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error al actualizar el término de venta.');
        }

        $this->jsonSuccess($this->langArr['term_updated']);
    }
}
