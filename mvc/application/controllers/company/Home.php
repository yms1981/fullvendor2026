<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends Company_Controller
{
    protected $forceAppChange = 'company';
    protected function hasAccess(): bool
    {
        $role = $this->getRole();

        return $role === 'manager' || $role === 'seller';
    }

    function index()
    {
        $this->setApplication('company');
        redirect(company_url('dashboard'),'refresh');
    }

}
