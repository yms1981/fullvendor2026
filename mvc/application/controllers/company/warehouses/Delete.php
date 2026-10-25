<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

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

        $response = $this->destroy($warehouseId);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['warehouse_error_delete'] ?? '');
        }

        $this->jsonSuccess($this->langArr['deleted_success'] ?? '');
    }
}
