<?php

class AssignWarehouse extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function run()
    {
        $orderId = $this->input->post('order_id');
        $warehouseId = $this->input->post('warehouse_id');

        $response = $this->post('order/assign-warehouse', [
            'order_id'     => (int) $orderId,
            'warehouse_id' => (int) $warehouseId,
        ]);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error assigning warehouse.');
        }

        $this->jsonSuccess($this->langArr['orden_asignada_exito'] ?? 'Warehouse assigned successfully.', ['success' => true]);
    }

}
