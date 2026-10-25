<?php
require_once APPPATH . 'controllers/company/countries/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($countryId=null) {
        $country = $this->getObjectFromApi("{$this->entityPath}/{$countryId}");

        if ($this->hasError($country)) {
            show_404();
        }

        $data['country'] = $country;
        $this->addJsFile("/companyasset/company/send_edit_form.js");

        $this->showContent('company/country/country_edit', $data);
    }

}
