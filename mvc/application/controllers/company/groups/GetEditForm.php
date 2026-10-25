<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/company/groups/Form.php';

class GetEditForm extends Form
{
    use IsAction;

    public function run($groupId = null)
    {
        if (!$groupId) {
            show_404();
            return;
        }

        $group = $this->getObjectFromApi("{$this->entityPath}/{$groupId}");

        if ($this->hasError($group)) {
            show_404();
            return;
        }

        $data['group'] = $group;
        $data['pageTexts'] = array_merge(
            $this->getLangJsonSection('cmp-toltip'),
            is_array($this->langArr) ? $this->langArr : []
        );

        // Cargar la vista del formulario de edición sin el wrapper completo
        $formContent = $this->load->view('company/groups/modals/edit_group_form', $data, true);
        
        echo $formContent;
    }
}

