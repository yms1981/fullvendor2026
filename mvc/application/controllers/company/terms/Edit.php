<?php
require_once APPPATH . 'controllers/company/terms/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($termId=null) {
        $term = $this->getObjectFromApi("{$this->entityPath}/{$termId}", ["company" => $this->getCompanyId()]);

        if ($this->hasError($term)) {
            show_404();
        }

        $data['term'] = $term;
        $this->addJsFile("/companyasset/company/send_edit_form_with_default.js");

        $this->showContent('company/terms/term_edit', $data);
    }

}
