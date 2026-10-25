<?php

require_once APPPATH . 'controllers/company/customers/Form.php';

class Add extends Form
{
    use IsAction;

    public function run(): void
    {
        $data = [];

        $data['termsOfSales'] = $this->getObjectFromApi('termsofsale',['status' => 1, 'props' => ['term_id', 'company_id', 'name'], 'all' => true]);
        $data['customerGroups'] = $this->getObjectFromApi('customergroup',['status' => 1, 'props' => ['group_id', 'company_id', 'name'], 'all' => true]);
        $data['sellers'] = $this->getObjectFromApi('vendor',['status' => 1, 'props' => ['id', 'company_id', 'user_id'], 'all' => true, 'relations' => ['user']]);

        $countriesResponse = $this->getObjectFromApi('country', ['props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'], 'all' => true, 'orderProperty' => 'name']);
        $data['countries'] = (is_object($countriesResponse) && isset($countriesResponse->data)) ? $countriesResponse->data : (is_array($countriesResponse) ? $countriesResponse : []);

        $company = null;
        $companyId = (int) $this->getCompanyId();
        if ($companyId > 0) {
            $companyResp = $this->getObjectFromApi('company/' . $companyId);
            if (!$this->hasError($companyResp)) {
                $company = $companyResp;
            }
        }
        $data['companyDefaultCountryId'] = $this->resolveCompanyDefaultCountryId($company, $data['countries']);

        $planLimits = $this->fetchCompanyPlanLimits();
        $data['planLimitBlock'] = $this->buildPlanLimitBlock($planLimits, 'customers', 'cmp-cliente');

        $this->addJsFile(base_url('companyasset/js/select2.min.js'));
        $this->addJsFile(base_url('companyasset/company/send_add_form.js'));
        $this->addJsFile(base_url('companyasset/company/customer/locationCascadeSelects.js'));

        $this->showContent('company/customer/customer_add', $data);
    }
}
