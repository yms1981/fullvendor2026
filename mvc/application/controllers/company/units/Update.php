<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
    }

    function run($unitId=null)
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();

        $response = $this->put("{$this->entityPath}/{$unitId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error al actualizar la unidad.');
        }

        $this->jsonSuccess($this->langArr['unit_updated']);
    }
}
