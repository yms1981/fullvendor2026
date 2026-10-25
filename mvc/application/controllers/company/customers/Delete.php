<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customer', 'company/customer/', 'customers');
        $this->langArr = $this->getModuleLang('cmp-clientes');
    }
    public function run($customer_id)
    {
        $response = $this->destroy($customer_id);

        if ($this->hasError($response)){
            $this->jsonError($response, (string) ($this->langArr['customer_delete_error'] ?? ''));
        }

        $this->jsonSuccess((string) ($this->langArr['cliente_eliminado'] ?? ''));
    }

}
