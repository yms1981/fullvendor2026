<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class BulkDelete extends Company_Controller
{
    public function index()
    {
        $postData = json_decode(file_get_contents('php://input'), true);

        $response = $this->apiDelete("customer/bulkDelete", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error deleting customers.');
        }

        $this->jsonSuccess('Customers deleted successfully.');
    }
}
