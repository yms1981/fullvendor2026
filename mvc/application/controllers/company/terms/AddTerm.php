<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/company/terms/Form.php';

class AddTerm extends Form
{
    use IsAction;

    public function run()
    {
        // Obtener el término por defecto si existe
        $response = $this->getObjectFromApi("{$this->entityPath}/company/{$this->getCompanyId()}");
        $data['defaultTerm'] = $this->hasError($response) ? null : $response;
        $data['pageTexts'] = $this->langArr;

        // Cargar la vista del formulario de agregar sin el wrapper completo
        $formContent = $this->load->view('company/terms/modals/add_term_form', $data, true);
        
        echo $formContent;
    }
}
