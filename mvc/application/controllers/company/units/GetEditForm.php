<?php
require_once APPPATH . 'controllers/company/units/Form.php';

class GetEditForm extends Form
{
    use IsAction;

    public function run($unitId = null)
    {
        if (!$unitId) {
            show_404();
            return;
        }

        $unit = $this->getObjectFromApi("{$this->entityPath}/{$unitId}", ["company" => $this->getCompanyId()]);

        if ($this->hasError($unit)) {
            show_404();
            return;
        }

        // Obtener la unidad por defecto
        $response = $this->getObjectFromApi("{$this->entityPath}/company/{$this->getCompanyId()}");

        $data['unit'] = $unit;
        $data['defaultUnit'] = $this->hasError($response) ? null : $response;
        $data['pageTexts'] = $this->langArr;

        // Cargar la vista del formulario de edición sin el wrapper completo
        $formContent = $this->load->view('company/units/modals/edit_unit_form', $data, true);
        
        echo $formContent;
    }
}

