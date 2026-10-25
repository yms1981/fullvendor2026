<?php
require_once APPPATH . 'controllers/company/orders/Form.php';
class Add extends Form
{

    use IsAction;

    public function run()
    {
        $duplicated = $this->input->get('duplicated');

        if ($duplicated) {
            $duplicatedOrder = json_decode(urldecode($duplicated), true);

            $customer_id = $duplicatedOrder['customer_id'];
            $data['duplicated'] = true;

            $data['order'] = $duplicatedOrder;
            $data['tipo_d'] = (isset($duplicatedOrder['tipo_d']) && $duplicatedOrder['tipo_d'] === 'C') ? 'C' : 'D';
        } else {
            $request = $this->input->get();
            $customer_id = $request['customer'] ?? null;
            $data['tipo_d'] = (isset($request['tipo_d']) && $request['tipo_d'] === 'C') ? 'C' : 'D';

            $importDetailRaw = $request['import_detail'] ?? null;
            if (is_string($importDetailRaw) && $importDetailRaw !== '') {
                $importDetails = json_decode($importDetailRaw, true);
                if (is_array($importDetails) && $importDetails !== []) {
                    $data['order'] = ['details' => $importDetails];
                }
            }
        }

        $customer = $this->getObjectFromApi("customer/{$customer_id}", ['relations' => ['sellers', 'customer_group']]);

        if ($this->hasError($customer)) {
            show_404();
        }

        $data['customer'] = $customer;
        $companyId = (int) $this->getCompanyId();
        $customersResponse = $this->getObjectFromApi("company/{$companyId}/customers");
        $data['customers'] = $this->hasError($customersResponse)
            ? $this->extractApiListItems($this->getDataFromApi('customer', ['props' => ['customer_id', 'name', 'business_name'], 'all' => true]))
            : $this->extractApiListItems($customersResponse);
        $data['sellers'] = $this->getDataFromApi("vendor", ['relations' => ['user'], 'props' => ['id', 'user_id','company_id'], 'all' => true]);

        try {
            $data['moneda'] = $companyId ? $this->getRawFromApi("company/{$companyId}/currency-symbol", []) : '';
            $data['format_numeric'] = $companyId ? $this->getObjectFromApi("company/{$companyId}/format-numeric") : null;
        } catch (\Exception $e) {
            $data['moneda'] = '';
            $data['format_numeric'] = null;
        }
        if (empty($data['moneda']) || (is_array($data['moneda']) && isset($data['moneda']['error']))) {
            $data['moneda'] = '$';
        }
        if (!$data['format_numeric'] || (is_object($data['format_numeric']) && isset($data['format_numeric']->error))) {
            $data['format_numeric'] = (object)['digits' => 2, 'radixPoint' => ',', 'groupSeparator' => '.'];
        }

        $planLimits = $this->fetchCompanyPlanLimits();
        $data['planLimitBlock'] = $this->buildPlanLimitBlock($planLimits, 'orders_month', 'cmp-orders');

        $data = $this->enrichOrderFormData($data, $customer);
        $data['pageTexts'] = ($this->langArr ?? []) + ($data['pageTexts'] ?? []);

        $this->addJsFile("/companyasset/company/order/order-numeric.js");
        $this->addJsFile("/companyasset/company/order/Form.js");
        $this->addJsFile("/companyasset/company/order/index.js");

        $this->showContent('company/order/order_add', $data);
    }
}
