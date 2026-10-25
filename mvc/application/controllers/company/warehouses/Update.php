<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('warehouse', 'company/warehouse/', 'warehouses');
        $this->langArr = $this->getModuleLang('cmp-warehouse');
    }

    public function run($warehouseId = null)
    {
        $warehouseId = (int) $warehouseId;
        if ($warehouseId < 1) {
            show_404();
        }

        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();

        $response = $this->put("{$this->entityPath}/{$warehouseId}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['warehouse_error_save'] ?? '');
        }

        $this->jsonSuccess($this->langArr['warehouse_updated'] ?? '');
    }
}
