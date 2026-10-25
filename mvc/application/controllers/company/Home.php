<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends Company_Controller
{
    protected $forceAppChange = 'company';
    protected function hasAccess(): bool
    {
        return $this->getRole() == 'manager' /* || $this->getRole() == 'company'*/ ;
    }

    function index()
    {
        $this->setApplication('company');
        redirect(company_url('dashboard'),'refresh');
    }

}
