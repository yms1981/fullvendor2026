<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH . 'controllers/company/groups/Form.php';

class AddGroup extends Form
{
    use IsAction;

    public function run()
    {
        $response = $this->getObjectFromApi("company/{$this->getCompanyId()}", ["props" => ['default_customer_group_id']]);
        $data['defaultGroup'] = $this->hasError($response) ? null : $response->default_customer_group_id;
        $data['pageTexts'] = array_merge(
            $this->getLangJsonSection('cmp-toltip'),
            is_array($this->langArr) ? $this->langArr : []
        );
        $formContent = $this->load->view('company/groups/modals/add_group_form', $data, true);
        echo $formContent;
    }
}

