<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('country', 'company/countries', 'countries');
    }

    public function run()
    {
        $postData = $this->input->post();

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error creating country.');
        }

        $this->jsonSuccess('Country saved successfully.');
    }

}
