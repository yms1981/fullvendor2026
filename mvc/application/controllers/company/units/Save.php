<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
    }

    public function run()
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error al crear la unidad de medida.');
        }

        $this->jsonSuccess($this->langArr['unit_saved']);
    }

}
