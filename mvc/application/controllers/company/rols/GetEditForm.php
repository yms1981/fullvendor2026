<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/company/rols/Form.php';

class GetEditForm extends Form
{
    use IsAction;

    public function run($rolId = null)
    {
        if (!$rolId) {
            show_404();
            return;
        }

        $rol = $this->getObjectFromApi("{$this->entityPath}/{$rolId}");

        if ($this->hasError($rol)) {
            show_404();
            return;
        }

        // Cargar el módulo de idioma
        $this->langArr = $this->getModuleLang('cmp-rol');
        $data['rol'] = $rol;
        $data['pageTexts'] = $this->langArr;

        // Cargar la vista del formulario de edición sin el wrapper completo
        $formContent = $this->load->view('company/rols/modals/edit_rol_form', $data, true);
        
        echo $formContent;
    }
}

