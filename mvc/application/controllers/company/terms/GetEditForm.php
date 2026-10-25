<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/company/terms/Form.php';

class GetEditForm extends Form
{
    use IsAction;

    public function run($termId = null)
    {
        if (!$termId) {
            show_404();
            return;
        }

        $term = $this->getObjectFromApi("{$this->entityPath}/{$termId}");

        if ($this->hasError($term)) {
            show_404();
            return;
        }

        $data['term'] = $term;
        $data['pageTexts'] = $this->langArr;

        // Cargar la vista del formulario de edición sin el wrapper completo
        $formContent = $this->load->view('company/terms/modals/edit_term_form', $data, true);
        
        echo $formContent;
    }
}

