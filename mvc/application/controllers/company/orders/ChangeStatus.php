<?php

class ChangeStatus extends Company_Controller
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
        $statusId = $this->input->post('status_id');
        $codStatus = $this->input->post('cod_status');

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order required']);
            return;
        }

        // Obtener la orden actual para validar reglas de estados
        $order = $this->getObjectFromApi("{$this->entityPath}/{$orderId}");
        if ($this->hasError($order) || !$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }
        // API expone order_status = cod_status (OrderListResource)
        $currentCodStatus = (int) ($order->order_status ?? $order->cod_status ?? (isset($order->statusOrder->cod_status) ? (int) $order->statusOrder->cod_status : 0));

        // Regla: Estado 9 o 10 → no se puede cambiar
        if (in_array($currentCodStatus, [9, 10], true)) {
            echo json_encode(['success' => false, 'message' => $this->langArr['no_se_puede_cambiar_estado'] ?? 'Cannot change status for this order']);
            return;
        }

        // Determinar el nuevo cod_status
        $newCodStatus = null;
        if ($codStatus !== null && $codStatus !== '') {
            $newCodStatus = (int) $codStatus;
            $data = ['cod_status' => $newCodStatus];
        } elseif ($statusId !== null && $statusId !== '') {
            $data = ['status_id' => (int) $statusId];
        } else {
            echo json_encode(['success' => false, 'message' => 'Status (status_id or cod_status) required']);
            return;
        }

        // Regla: Estado 11 → solo se puede cambiar a 7 o 14
        if ($currentCodStatus === 11 && $newCodStatus !== null) {
            if (!in_array($newCodStatus, [7, 14], true)) {
                echo json_encode(['success' => false, 'message' => $this->langArr['no_se_puede_cambiar_estado'] ?? 'Cannot change to this status']);
                return;
            }
        }

        $response = $this->patch("{$this->entityPath}/{$orderId}/status", $data);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error changing order status.');
        }

        $this->jsonSuccess($this->langArr['status_cambiando'] ?? 'Order status changed');
    }

}
