<?php

require_once APPPATH . 'controllers/company/userprofile/Form.php';

class Edit extends Form
{
    use IsAction;

    public function run($profileId = null)
    {
        $profile = $this->getObjectFromApi("{$this->entityPath}/{$profileId}");

        if ($this->hasError($profile)) {
            show_404();
        }

        $data['profile'] = $profile;
        $this->addJsFile("/companyasset/company/send_edit_form.js");
        $this->showContent('company/userprofile/userprofile_edit', $data, 'company');
    }
}

