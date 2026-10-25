<?php

require_once APPPATH . 'controllers/company/sellers/Form.php';

class Add extends Form
{
    use IsAction;

    public function run(): void
    {
        $response = $this->getObjectFromApi("company/{$this->getCompanyId()}",["props" => ['default_seller_id']]);

        $data = [];
        $data['defaultSeller'] = $this->hasError($response) ? null : $response->default_seller_id;
        $data['sellerProfiles'] = $this->getObjectFromApi('sellerprofiles', [
            'status' => 1,
            'props' => ['profile_id', 'profile_name', 'status', 'default'],
            'all' => true
        ]);

        $this->addJsFile("/companyasset/company/users/userSearch.js");
        $this->addJsFile("/companyasset/company/send_add_form_with_default.js");
        $this->showContent('company/seller/seller_add', $data);
    }
}
