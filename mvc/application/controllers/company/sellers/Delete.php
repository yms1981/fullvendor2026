<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('vendor', 'company/seller/', 'sellers');
        $this->langArr = $this->getModuleLang('cmp-user');
    }
    public function run($seller_id)
    {
        $response = $this->destroy($seller_id);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error deleting seller.');
        }

        $this->jsonSuccess('Seller deleted successfully.');
    }

}
