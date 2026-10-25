<?php
require_once APPPATH . 'controllers/company/states/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($stateId=null) {
        $state = $this->getObjectFromApi("{$this->entityPath}/{$stateId}",['relations' => 'country']);

        if ($this->hasError($state)) {
            show_404();
        }

        $data['state'] = $state;

        $this->addJsFile("/companyasset/company/send_edit_form.js");
        $this->addJsFile('/companyasset/company/locations/countrySearch.js');

        $this->showContent('company/state/state_edit', $data);
    }

}
