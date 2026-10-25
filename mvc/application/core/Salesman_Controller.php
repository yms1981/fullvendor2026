<?php

class Salesman_Controller extends Application_Controller
{

    protected string $layout = 'salesman';

    public function __construct()
    {
        parent::__construct();
        $this->addJsFile('/companyasset/js/jquery-3.5.1.js');
        $this->addJsFile('/companyasset/antomi/assets/js/plugins.js');
        $this->addJsFile('/companyasset/antomi/assets/js/sweetalert2.all.min.js');

    }

    protected function getSellerInfo($sellerId) {
        $persitedKey = 'info-seller-' . $sellerId;
        return $this->getPersistedOrCompute($persitedKey,
            fn() =>
            $this->getObjectFromApi(
                "vendor/{$sellerId}",
                ['relations' => ['user']]
            )
        );
    }

    protected function getCompanyInfo($companyId) {
        $persitedKey = 'info-company-' . $companyId;
        return $this->getPersistedOrCompute($persitedKey,
            fn() =>
            $this->getObjectFromApi(
                "company/{$companyId}",
                ['relations' => ['currencies','formatDate','format_numeric']]
            )
        );
    }

    protected function getCustomerInfo($customerId) {
        $persitedKey = 'info-customer-' . $customerId;
        return $this->getPersistedOrCompute($persitedKey,
            fn() =>
            $this->getObjectFromApi(
                "customer/{$customerId}",
                ['relations' => ['customer_group']]
            )
        );
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $this->benchmark->mark('before_salesman_data_for_view');
        $dataForView['info_seller'] = $this->getSellerInfo($this->getSellerId());
        $dataForView['info_company'] = $this->getCompanyInfo($this->getCompanyId());
        $dataForView['info_customer'] = $this->getCustomerInfo($this->getCustomerId());
        $dataForView['user'] = $this->getUser();
        $dataForView['user']['role'] = $this->getRole();
        $this->benchmark->mark('after_salesman_data_for_view');
        return parent::getDataForView($view, $dataForView);
    }

    public function showContent($contentView, $data = [], $layout = 'salesman')
    {
        $this->benchmark->mark('before_salesman_show_content');
        $data['base_url'] = seller_url('catalog');
        $this->benchmark->mark('after_salesman_show_content');
        $this->addJsFile('/companyasset/js/jquery-3.5.1.js');
        parent::showContent($contentView, $data, $layout);
    }
}
