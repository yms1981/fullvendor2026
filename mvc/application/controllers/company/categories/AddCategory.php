<?php
require_once APPPATH . 'controllers/company/categories/Form.php';

class AddCategory extends Form
{
    use IsAction;

    public function run()
    {
        $data['pageTexts'] = array_merge(
            $this->getLangJsonSection('cmp-toltip'),
            is_array($this->langArr) ? $this->langArr : []
        );

        // Cargar la vista del formulario de agregar sin el wrapper completo
        $formContent = $this->load->view('company/categorias/modals/add_category_form', $data, true);
        
        echo $formContent;
    }
}

