<?php
require_once APPPATH . 'controllers/company/orders/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($orderId=null) {
        $order = $this->getObjectFromApi("{$this->entityPath}/{$orderId}",
            ['relations' =>
                [
                    'customer.sellers',
                    'customer.customer_group',
                    'order_details.product.images',
                    'statusOrder',
                    'warehouse',
                    'notes'

                ]
            ]);

        if ($this->hasError($order)) {
            show_404();
        }

        $data['order'] = $order;

        // Si la orden está en estado New (id_status = 1), pasarla automáticamente a Read (id_status = 2)
        $currentStatusId = isset($order->status_id) ? (int) $order->status_id : (isset($order->status_order->id_status) ? (int) $order->status_order->id_status : (isset($order->statusOrder->id_status) ? (int) $order->statusOrder->id_status : null));
        if ($currentStatusId === 1) {
            try {
                $this->patch("{$this->entityPath}/{$orderId}/status", ['status_id' => 2]);
            } catch (\Throwable $e) {
                log_message('error', 'Order Edit: could not set status to Read for order_id=' . $orderId . ': ' . $e->getMessage());
            }
        }

        $data['customers'] = $this->extractApiListItems($this->getDataFromApi("customer", ['props' => ['customer_id', 'name', 'business_name'], 'all' => true]));
        $data['sellers'] = $this->getDataFromApi("vendor", ['relations' => ['user'], 'props' => ['id', 'user_id','company_id'], 'all' => true]);

        $companyId = (int) $this->getCompanyId();
        $customersResponse = $this->getObjectFromApi("company/{$companyId}/customers");
        if (!$this->hasError($customersResponse)) {
            $data['customers'] = $this->extractApiListItems($customersResponse);
        }

        $data = $this->enrichOrderFormData($data, $order->customer ?? null);
        $data['pageTexts'] = ($this->langArr ?? []) + ($data['pageTexts'] ?? []);

        $this->addJsFile("/companyasset/company/order/order-numeric.js");
        $this->addJsFile("/companyasset/company/order/Form.js");
        $this->addJsFile("/companyasset/company/order/index.js");
        $this->addJsFile("/companyasset/company/order/ImagePopover.js");
        $this->showContent('company/order/order_edit.php', $data);
    }

}
