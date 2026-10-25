<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Devuelve detalles de órdenes para mostrar en el modal de confirmación de eliminación.
 * GET o POST order_ids[] → JSON con array de { order_number, customer, order_details }.
 */
class DeletePreview extends Company_Controller
{
    /**
     * GET o POST order_ids[] → JSON con lista de órdenes (order_number, customer, order_details).
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        $orderIds = $this->input->get_post('order_ids');
        if (!is_array($orderIds)) {
            $orderIds = $orderIds !== null && $orderIds !== '' ? [$orderIds] : [];
        }
        $orderIds = array_filter(array_map('intval', $orderIds));
        if (empty($orderIds)) {
            $this->output->set_output(json_encode(['success' => false, 'orders' => [], 'message' => 'No orders']));
            return;
        }

        $orders = [];
        foreach (array_slice($orderIds, 0, 20) as $orderId) {
            if ($orderId <= 0) {
                continue;
            }
            $response = $this->getObjectFromApi("order/{$orderId}/full");
            if ($this->hasError($response) || !$response) {
                $orders[] = ['order_id' => $orderId, 'error' => true, 'order_number' => '', 'customer' => null, 'order_details' => []];
                continue;
            }
            $order = isset($response->data) ? $response->data : $response;
            if (is_array($order)) {
                $order = (object) json_decode(json_encode($order), false);
            }

            $customer = null;
            if (!empty($order->customer)) {
                $c = is_object($order->customer) ? $order->customer : (object) $order->customer;
                $customer = [
                    'name' => $c->name ?? '',
                    'business_name' => $c->business_name ?? '',
                    'email' => $c->email ?? '',
                    'phone' => $c->phone ?? '',
                    'cell_phone' => $c->cell_phone ?? '',
                ];
            }

            $items = [];
            $details = $order->order_details ?? $order->orderDetails ?? [];
            if (!is_array($details)) {
                $details = [];
            }
            foreach ($details as $d) {
                $detail = is_object($d) ? $d : (object) $d;
                $product = $detail->product ?? null;
                $productName = '';
                $sku = '';
                if ($product) {
                    $p = is_object($product) ? $product : (object) $product;
                    $productName = $p->name ?? $p->descriptions ?? '';
                    $sku = $p->sku ?? '';
                }
                $items[] = [
                    'product_name' => $productName,
                    'sku' => $sku,
                    'qty' => (float) ($detail->qty ?? 0),
                    'price' => isset($detail->price) ? (float) $detail->price : null,
                    'total' => isset($detail->total) ? (float) $detail->total : null,
                ];
            }

            $orders[] = [
                'order_id' => $orderId,
                'order_number' => $order->order_number ?? '',
                'customer' => $customer,
                'order_details' => $items,
                'total_amount' => isset($order->total_amount) ? (float) $order->total_amount : null,
            ];
        }

        $this->output->set_output(json_encode(['success' => true, 'orders' => $orders]));
    }
}
