<?php
require_once APPPATH . 'controllers/company/cities/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($cityId=null) {
        $city = $this->getObjectFromApi("{$this->entityPath}/{$cityId}",['relations' => 'state']);

        if ($this->hasError($city)) {
            show_404();
        }

        $data['city'] = $city;

        $this->addJsFile("/companyasset/company/send_edit_form.js");
        $this->addJsFile('/companyasset/company/locations/stateSearch.js');

        $this->showContent('company/city/city_edit', $data);
    }

}
