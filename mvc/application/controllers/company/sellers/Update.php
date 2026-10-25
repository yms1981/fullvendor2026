<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('vendor', 'company/seller/', 'sellers');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    function run($sellerId=null)
    {
        $postData = $this->input->post();
        $response = $this->put("{$this->entityPath}/{$sellerId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error updating seller.');
        }

        $this->jsonSuccess('Seller updated successfully.');
    }
}
