<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchUser extends Company_Controller
{
    public function index()
    {
        $term = $this->input->get('term');
        $userParams = ['all' => true,'orderProperty' => 'name','props' => ['id', 'name','email'],'search' => $term];

        $response = $this->getObjectFromApi('user',$userParams);

        echo !$this->hasError($response) ? json_encode($response) : [];
    }
}
