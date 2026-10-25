<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchCustomer extends Company_Controller
{
    public function index()
    {
        $term = $this->input->get('term');
        $customerParams = ['all' => true,'orderProperty' => 'name','props' => ['customer_id', 'name', 'business_name', 'email'],'search' => $term];

        $response = $this->getObjectFromApi('customer',$customerParams);

        echo !$this->hasError($response) ? json_encode($response) : [];
    }
}
