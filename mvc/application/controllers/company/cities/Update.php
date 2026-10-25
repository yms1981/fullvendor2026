<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    function __construct()
    {
        parent::__construct();
        $this->initCrud('city', 'company/cities', 'cities');
        $this->langArr = [];
    }

    function run($cityId=null)
    {
        $postData = $this->input->post();
        $response = $this->put("{$this->entityPath}/{$cityId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error updating city.');
        }

        $this->jsonSuccess('City updated successfully.');
    }
}
