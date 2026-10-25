<?php

class Approve extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function run($orderId = null)
    {
        $response = $this->patch("{$this->entityPath}/{$orderId}/approve");

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error changing order status.');
        }

        $this->jsonSuccess($this->langArr['order_aprobada'] ?? 'Order approved.');
    }

}
