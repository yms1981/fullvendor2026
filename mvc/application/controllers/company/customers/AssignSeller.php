<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class AssignSeller extends Company_Controller
{
    public function index()
    {
        $postData = json_decode(file_get_contents('php://input'), true);

        $response = $this->post("customer/bulkAssignSeller", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error assigning seller.');
        }

        $this->jsonSuccess('Seller assigned successfully.');
    }
}
