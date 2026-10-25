<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . '/core/HasCatalog.php';

class Catalog extends Marketplace_Controller
{
    use HasCatalog;
    protected $accessControlRole = 'customer';

    protected function hasAccess(): bool
    {
        return $this->getRole() == 'customer' || $this->getRole() == 'manager';
    }

    public function index()
    {
        $this->showCatalog();
    }

}
