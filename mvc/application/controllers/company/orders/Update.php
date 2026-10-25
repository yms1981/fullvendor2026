<?php

class Update extends Company_Controller
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
        $json = $this->input->post('data');
        $data = json_decode($json, true);

        $response = $this->put("{$this->entityPath}/{$orderId}", $data);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error updating the order.');
        }

        $this->jsonSuccess($this->langArr['mensaje_success'] ?? 'Order updated');
    }

}
