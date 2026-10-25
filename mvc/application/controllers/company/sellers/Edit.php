<?php
require_once APPPATH . 'controllers/company/sellers/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($sellerId=null) {
        $seller = $this->getObjectFromApi("{$this->entityPath}/{$sellerId}", ['relations' => ['user.sellerCompanyUser.profileable']]);

        if ($this->hasError($seller)) {
            show_404();
        }

        $data['seller'] = $seller;
        $data['sellerProfiles'] = $this->getObjectFromApi('sellerprofiles', [
            'status' => 1,
            'props' => ['profile_id', 'profile_name', 'status', 'default'],
            'all' => true
        ]);

        $this->addJsFile("/companyasset/company/send_edit_form_with_default.js");
        $this->addJsFile("/companyasset/company/users/userSearch.js");

        $this->showContent('company/seller/seller_edit', $data);
    }

}
