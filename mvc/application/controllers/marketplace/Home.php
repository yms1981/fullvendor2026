<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends Marketplace_Controller
{
    use HasCatalog;
    protected $forceAppChange = 'marketplace';

    protected function hasAccess(): bool
    {
        return $this->getRole() == 'customer' || $this->getRole() == 'manager';
    }

    function index()
    {
        $this->setApplication('marketplace');
        $this->showCatalog();
    }
}
