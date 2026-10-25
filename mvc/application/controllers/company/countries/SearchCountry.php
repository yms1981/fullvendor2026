<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchCountry extends Company_Controller
{
    public function index()
    {
        $term = $this->input->get('term');
        $locationParams = ['all' => true,'orderProperty' => 'name','props' => ['id', 'name'],'name' => $term];

        $response = $this->getObjectFromApi('country',$locationParams);

        echo !$this->hasError($response) ? json_encode($response) : [];
    }
}
