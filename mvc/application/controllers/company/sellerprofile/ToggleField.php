<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/sellerprofile/Home.php';

/**
 * Compatibilidad: redirige al controlador principal Sellerprofile (misma lógica que Home).
 */
class ToggleField extends Home
{
    use IsAction;

    public function index($itemId = null, $fieldName = null)
    {
        if ($itemId === null) {
            show_404();
            return;
        }

        $this->runBooleanFieldToggle($itemId, $fieldName);
    }

    public function run($itemId, $fieldName = null): void
    {
        $this->runBooleanFieldToggle($itemId, $fieldName);
    }
}
