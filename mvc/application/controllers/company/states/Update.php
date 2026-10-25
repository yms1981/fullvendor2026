<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    function __construct()
    {
        parent::__construct();
        $this->initCrud('state', 'company/states', 'states');
        $this->langArr = [];
    }

    function run($stateId=null)
    {
        $postData = $this->input->post();
        $response = $this->put("{$this->entityPath}/{$stateId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error updating state.');
        }

        $this->jsonSuccess('State updated successfully.');
    }
}
