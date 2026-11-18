<?php
require_once APPPATH . 'controllers/company/customers/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($customerId=null) {
        // Direcciones: fuente de verdad = columnas commercial_*/dispatch_* en customers
        // (no pivot addresses; en algunos entornos la tabla addresses no existe).
        $customer = $this->getObjectFromApi("{$this->entityPath}/{$customerId}",
            ['relations' =>
                [
                    'users:id,name,email,password_plain,phone,cellphone',
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
        $data['company'] = $company;
        $data['companyDefaultCountryId'] = $this->resolveCompanyDefaultCountryId($company, $data['countries']);

        $data['apiToken'] = $this->getUserToken();
        $data['apiBaseUrl'] = '/api';

        // Modal alta usuario (mismo de Users) preseleccionando este customer.
        $cmpUserLang = $this->getLangJsonSection('cmp-user');
        $data['userModalPageTexts'] = is_array($cmpUserLang) ? $cmpUserLang : [];
        $data['userModalCustomerLabels'] = $this->getLangJsonSection('cmp-clientes');
        $data['userModalCustomers'] = [$customer];
        $data['userModalSellerProfiles'] = [];
        $data['userModalListReloadUrl'] = company_url('customers/edit/' . (int) ($customer->customer_id ?? 0));
        $data['planLimitBlock'] = ['can_create' => true];
        $data['companyPhoneCountryMeta'] = $this->buildCompanyPhoneCountryMeta($company);
        $data['companyUserCountryFlagsUrl'] = rtrim((string) base_url(), '/') . '/images/flags/';

        $customerIdInt = (int) ($customer->customer_id ?? $customerId ?? 0);
        $data['customerLastCatalogs'] = $this->loadCustomerLastCatalogs($companyId, $customerIdInt, 5);
        $data['customerLastOrders'] = $this->loadCustomerLastOrders($customerIdInt, 5);
        $currencySymbol = '';
        if (is_object($company) && !empty($company->currencies[0]->symbol)) {
            $currencySymbol = (string) $company->currencies[0]->symbol;
        } elseif (is_object($company) && !empty($company->currency->symbol)) {
            $currencySymbol = (string) $company->currency->symbol;
        }
        $data['customerLastOrdersCurrency'] = $currencySymbol;

        $this->addJsFile(base_url('companyasset/js/sweetalert2.js'));
        $this->addJsFile(base_url('companyasset/js/select2.min.js'));
        $this->addJsFile(base_url('companyasset/company/send_edit_form.js'));
        $this->addJsFile(base_url('companyasset/company/customer/locationCascadeSelects.js'));
        $this->addJsFile(base_url('companyasset/company/unassign_assigned_user.js'));
        $this->addJsFile(base_url('companyasset/company/customer/last-activity.js'));
        $this->addJsFile(base_url('companyasset/company/users/send_form.js'));
        $this->addJsFile(base_url('companyasset/company/users/user_list_modal.js'));
        $this->addJsFile(base_url('companyasset/company/users/user_edit_modal.js'));

        $this->showContent('company/customer/customer_edit', $data);
    }

    /**
     * @return list<object>
     */
    private function loadCustomerLastCatalogs(int $companyId, int $customerId, int $limit = 5): array
    {
        if ($companyId < 1 || $customerId < 1) {
            return [];
        }

        $resp = $this->getObjectFromApi(
            "catalogcart/company/{$companyId}/customer/{$customerId}/recent",
            ['limit' => $limit]
        );
        if ($this->hasError($resp)) {
            return [];
        }
        if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
            return array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $resp->data);
        }
        if (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
            return array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $resp['data']);
        }

        return [];
    }

    /**
     * @return list<object>
     */
    private function loadCustomerLastOrders(int $customerId, int $limit = 5): array
    {
        if ($customerId < 1) {
            return [];
        }

        $resp = $this->getObjectFromApi('order', [
            'customer_id' => $customerId,
            'per_page' => $limit,
            'page' => 1,
            'order_sort' => 'created',
            'order_type' => 'DESC',
            'props' => ['order_id', 'order_number', 'created', 'total_amount', 'amount', 'source'],
            'relations' => ['seller.user', 'statusOrder'],
        ]);
        if ($this->hasError($resp)) {
            return [];
        }
        $rows = [];
        if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
            $rows = $resp->data;
        } elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
            $rows = $resp['data'];
        } elseif (is_array($resp)) {
            $rows = $resp;
        }

        return array_map(static function ($row) {
            $o = is_object($row) ? $row : (object) $row;
            $seller = is_object($o->seller ?? null) ? $o->seller : (object) [];
            $sellerUser = is_object($seller->user ?? null) ? $seller->user : (object) [];
            $sellerName = trim((string) ($sellerUser->name ?? ''));
            if ($sellerName === '') {
                $sellerName = trim(((string) ($sellerUser->first_name ?? '')) . ' ' . ((string) ($sellerUser->last_name ?? '')));
            }
            $status = is_object($o->statusOrder ?? null) ? $o->statusOrder : (object) [];
            $o->vendedor = $sellerName;
            $o->status_label = (string) ($status->name_status ?? $status->name_status_english ?? '');
            $o->status_color = (string) ($status->color ?? '#94a3b8');
            $o->total_display = $o->total_amount ?? $o->amount ?? $o->total ?? 0;

            return $o;
        }, $rows);
    }

    /**
     * @param object|null $company
     * @return array{id:string,sortname:string,phonecode:int,phonemask:string,name:string}
     */
    private function buildCompanyPhoneCountryMeta($company): array
    {
        $meta = [
            'id' => '',
            'sortname' => '',
            'phonecode' => 0,
            'phonemask' => '',
            'name' => '',
        ];
        $countryId = null;
        if (is_object($company) && isset($company->country)) {
            $countryId = $company->country;
        }
        if ($countryId === null || $countryId === '') {
            return $meta;
        }
        $c = $this->getObjectFromApi('country/' . (int) $countryId, [
            'props' => ['id', 'sortname', 'phonecode', 'phonemask', 'name'],
        ]);
        if ($this->hasError($c) || ! is_object($c)) {
            return $meta;
        }
        $meta['id'] = (string) ($c->id ?? '');
        $meta['sortname'] = strtolower((string) ($c->sortname ?? ''));
        $meta['phonecode'] = (int) ($c->phonecode ?? 0);
        $meta['phonemask'] = (string) ($c->phonemask ?? '');
        $meta['name'] = (string) ($c->name ?? '');

        return $meta;
    }
}
