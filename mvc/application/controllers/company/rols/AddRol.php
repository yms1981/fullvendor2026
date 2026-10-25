<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH . 'controllers/company/rols/Form.php';

class AddRol extends Form
{
    use IsAction;

    public function run()
    {
        // Cargar el módulo de idioma
        $this->langArr = $this->getModuleLang('cmp-rol');
        $data['pageTexts'] = $this->langArr;
        $formContent = $this->load->view('company/rols/modals/add_rol_form', $data, true);
        echo $formContent;
    }
}

