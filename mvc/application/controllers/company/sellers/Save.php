<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('vendor', 'company/seller/', 'sellers');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    public function run(): void
    {
        $postData = $this->input->post();

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, 'Error creating seller.');
        }

        $this->jsonSuccess('Seller saved successfully.');
    }

}
