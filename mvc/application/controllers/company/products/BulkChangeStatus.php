<?php

class BulkChangeStatus extends Company_Controller
{
    use IsCRUD;
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
        $this->langArr = $this->getModuleLang('cmp-products');
    }

    public function index() {
        $request = (object)$this->input->post();
        $request->status = $request->status_toupdate; /* TODO: replace status_toupdate with status on from/js*/
        $errorMsg = $this->langArr['error_general'] ?? 'Error';
        echo $this->bulkUpdate(
            $request,
            'status',
            $errorMsg,
            $request->status == '1' ? "enabled_success" : 'disabled_success'
        );
    }
}
