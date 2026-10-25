<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Form extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('rols', 'company/rols/', 'roles');
    }
}

