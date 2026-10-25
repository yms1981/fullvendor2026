<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class UnassignSeller extends Company_Controller
{
    public function index()
    {
        $postData = json_decode(file_get_contents('php://input'), true);

        $response = $this->post("customer/bulkUnassignSeller", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error unassigning seller.');
        }

        $this->jsonSuccess('Seller unassigned successfully.');
    }
}
