<?php
require_once APPPATH . 'controllers/company/customers/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($customerId=null) {
        $customer = $this->getObjectFromApi("{$this->entityPath}/{$customerId}",
            ['relations' =>
                [
                    'addresses.country:id,name',
                    'addresses.state:id,name',
                    'addresses.city:id,name',
                    'users:id,name,email',
                    'emails'
                ]
            ]);

        if ($this->hasError($customer)) {
            show_404();
        }

        $data['customer'] = $customer;

        $data['termsOfSales'] = $this->getObjectFromApi('termsofsale',['status' => 1, 'props' => ['term_id', 'company_id', 'name'], 'all' => true]);
        $data['customerGroups'] = $this->getObjectFromApi('customergroup',['status' => 1, 'props' => ['group_id', 'company_id', 'name'], 'all' => true]);
        $data['sellers'] = $this->getObjectFromApi('vendor',['status' => 1, 'props' => ['id', 'company_id', 'user_id'], 'all' => true, 'relations' => ['user']]);
        $countriesResponse = $this->getObjectFromApi('country', ['props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'], 'all' => true]);
        $data['countries'] = (is_object($countriesResponse) && isset($countriesResponse->data))
            ? $countriesResponse->data
            : (is_array($countriesResponse) ? $countriesResponse : []);

        $company = null;
        $companyId = (int) $this->getCompanyId();
        if ($companyId > 0) {
            $companyResp = $this->getObjectFromApi('company/' . $companyId);
            if (!$this->hasError($companyResp)) {
                $company = $companyResp;
            }
        }
        $data['companyDefaultCountryId'] = $this->resolveCompanyDefaultCountryId($company, $data['countries']);

        $data['apiToken'] = $this->getUserToken();
        // Usar ruta relativa para que funcione desde el navegador
        $data['apiBaseUrl'] = '/api';

        $this->addJsFile(base_url('companyasset/js/select2.min.js'));
        $this->addJsFile(base_url('companyasset/company/send_edit_form.js'));
        $this->addJsFile(base_url('companyasset/company/users/userSearch.js'));
        $this->addJsFile(base_url('companyasset/company/customer/locationCascadeSelects.js'));
        $this->addJsFile(base_url('companyasset/company/assign_user.js'));
        $this->addJsFile(base_url('companyasset/company/edit_assigned_user.js'));

        $this->showContent('company/customer/customer_edit', $data);
    }

}
