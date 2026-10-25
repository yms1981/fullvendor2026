<?php

namespace controllers;

use GenericCatalog;

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . '/controllers/GenericCatalog.php';

class CompanyCatalog extends GenericCatalog
{
    public $indexJs = ['enviroment_portal.js', 'get_language.js', 'shoplist.js', 'app.js'];
    public $cartJs = ['enviroment_portal.js', 'get_language.js', 'placeorder.js', 'check_newaddress.js'];

    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
    }


}
