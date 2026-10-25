<?php
require_once APPPATH . 'controllers/company/units/Form.php';

class AddUnit extends Form
{
    use IsAction;

    public function run()
    {
        $data = [];
        $response = $this->getObjectFromApi("{$this->entityPath}/company/{$this->getCompanyId()}");

        $data['defaultUnit'] = $this->hasError($response) ? null : $response;
        $data['pageTexts'] = $this->langArr;

        // Cargar la vista del formulario de agregar sin el wrapper completo
        $formContent = $this->load->view('company/units/modals/add_unit_form', $data, true);
        
        echo $formContent;
    }
}

