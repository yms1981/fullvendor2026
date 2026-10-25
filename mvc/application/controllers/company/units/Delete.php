<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
    }
    public function run($unit_id)
    {
        $response = $this->destroy($unit_id, ['company' => $this->getCompanyId()]);

        if ($this->hasError($response)){
            $this->jsonError($response, $this->langArr['error_general'] ?? 'Error');
        }

        $this->jsonSuccess($this->langArr['deleted_success'] ?? '', ['success' => true]);
    }

}
