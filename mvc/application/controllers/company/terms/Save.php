<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('termsofsale', 'company/term/', 'terms');
        $this->langArr = $this->getModuleLang('cmp-terms');
    }

    public function run()
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error al crear el término de venta.');
        }

        $this->jsonSuccess($this->langArr['term_saved']);
    }

}
