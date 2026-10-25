<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('termsofsale', 'company/term/', 'terms');
        $this->langArr = $this->getModuleLang('cmp-terms');
    }
    public function run($term_id)
    {
        $response = $this->destroy($term_id, ['company' => $this->getCompanyId()]);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error al eliminar el término de venta.');
        }

        $this->jsonSuccess($this->langArr['deleted_success']);
    }

}
