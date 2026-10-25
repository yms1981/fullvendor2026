<?php
require_once APPPATH . 'controllers/company/units/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($unitId=null)
    {
        $unit = $this->getObjectFromApi("{$this->entityPath}/{$unitId}", ["company" => $this->getCompanyId()]);

        if ($this->hasError($unit)) {
            show_404();
        }

        $data['unit'] = $unit;
        $this->addJsFile("/companyasset/company/send_edit_form_with_default.js");

        $this->showContent('company/units/units_edit', $data);
    }

}
