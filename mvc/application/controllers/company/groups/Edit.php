<?php
require_once APPPATH . 'controllers/company/groups/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($groupId=null)
    {
        $group = $this->getObjectFromApi("{$this->entityPath}/{$groupId}");

        if ($this->hasError($group)) {
            show_404();
        }

        $data['group'] = $group;
        $this->addJsFile("/companyasset/company/send_edit_form_with_default.js");
        $this->addJsFile("/companyasset/company/groups/price-adjustment.js");

        $this->showContent('company/groups/group_edit', $data);
    }

}
