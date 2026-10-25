<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    function __construct()
    {
        parent::__construct();
        $this->initCrud('country', 'company/countries', 'countries');
        $this->langArr = [];
    }

    function run($countryId=null)
    {
        $postData = $this->input->post();
        $response = $this->put("{$this->entityPath}/{$countryId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error updating country.');
        }

        $this->jsonSuccess('Country updated successfully.');
    }
}
