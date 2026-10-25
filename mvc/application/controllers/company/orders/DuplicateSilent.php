<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Duplica una o más órdenes en silencio: mismo cliente, nuevo número de orden.
 * POST order_ids[] → para cada orden obtiene full, construye payload y POST a API order.
 */
class DuplicateSilent extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $langId = $this->getUserData('language_id');
        $this->langArr = $this->langmodel->getMdl('cmp-orders', $langId);
        if (!is_array($this->langArr)) {
            $this->langArr = $this->getModuleLang('cmp-orders');
        }
    }

    /**
     * POST order_ids[] → duplica cada orden (mismo cliente, nueva orden vía API).
     * Responde JSON: { success, duplicated: [{ order_id, new_order_id, new_order_number }], failed: [...], message }
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method(true) !== 'POST') {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $this->langArr['alert_select_at_least_one'] ?? 'Seleccione al menos una orden.',
            ]));
            return;
        }

        $orderIds = $this->input->post('order_ids');
        if (!is_array($orderIds)) {
            $orderIds = $orderIds !== null && $orderIds !== '' ? [$orderIds] : [];
        }
        $orderIds = array_filter(array_map('intval', $orderIds));
        if (empty($orderIds)) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $this->langArr['alert_select_at_least_one'] ?? 'Seleccione al menos una orden.',
            ]));
            return;
        }

        $duplicated = [];
        $failed = [];

        foreach ($orderIds as $orderId) {
            if ($orderId <= 0) {
                continue;
            }
            $result = $this->duplicateOneOrder($orderId);
            if ($result['success']) {
                $duplicated[] = [
                    'order_id' => $orderId,
                    'new_order_id' => $result['new_order_id'],
                    'new_order_number' => $result['new_order_number'] ?? '',
                ];
            } else {
                $failed[] = [
                    'order_id' => $orderId,
                    'message' => $result['message'] ?? 'Error',
                ];
            }
        }

        $success = count($duplicated) > 0;
        $msg = $success
            ? sprintf(
                $this->langArr['orders_duplicated_success'] ?? 'Se duplicaron %d orden(es) correctamente.',
                count($duplicated)
            )
            : ($this->langArr['orders_duplicate_error'] ?? 'No se pudo duplicar ninguna orden.');

        $this->output->set_output(json_encode([
            'success' => $success,
            'duplicated' => $duplicated,
            'failed' => $failed,
            'message' => $msg,
        ]));
    }

    /**
     * Duplica una orden: obtiene full, construye payload API y POST.
     * @return array { success, new_order_id?, new_order_number?, message? }
     */
    protected function duplicateOneOrder($orderId)
    {
        $response = $this->getObjectFromApi("{$this->entityPath}/{$orderId}/full");
        if ($this->hasError($response) || !$response) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $order = isset($response->data) ? $response->data : $response;
        if (is_array($order)) {
            $order = (object) json_decode(json_encode($order), false);
        }

        $customerId = $order->customer_id ?? null;
        if (empty($customerId)) {
            return ['success' => false, 'message' => 'Order has no customer'];
        }

        $products = $this->buildProductsFromOrder($order);
        if (empty($products)) {
            return ['success' => false, 'message' => 'Order has no products'];
        }

        $payload = [
            'customer_id' => (int) $customerId,
            'seller_id' => (int) ($order->seller_id ?? 0),
            'discount_type' => (string) ($order->discount_type ?? '1'),
            'discount' => (float) ($order->discount ?? 0),
            'internal_comments' => $this->extractInternalNotes($order),
            'tipo_d' => $order->tipo_d ?? 'D',
            'proforma' => !empty($order->proforma),
            'products' => $products,
        ];

        $createResponse = $this->post("{$this->entityPath}", $payload);
        if ($this->hasError($createResponse)) {
            $msg = is_object($createResponse) ? ($createResponse->message ?? 'API error') : ($createResponse['message'] ?? 'API error');
            return ['success' => false, 'message' => $msg];
        }

        $newOrderId = null;
        $newOrderNumber = '';
        $data = is_object($createResponse) ? ($createResponse->data ?? $createResponse) : ($createResponse['data'] ?? $createResponse);
        if (is_object($data)) {
            $newOrderId = $data->order_id ?? $data->id ?? null;
            $newOrderNumber = $data->order_number ?? '';
        } elseif (is_array($data)) {
            $newOrderId = $data['order_id'] ?? $data['id'] ?? null;
            $newOrderNumber = $data['order_number'] ?? '';
        }
        if (!$newOrderId && is_object($createResponse) && isset($createResponse->order_id)) {
            $newOrderId = $createResponse->order_id;
            $newOrderNumber = $createResponse->order_number ?? '';
        }

        return [
            'success' => true,
            'new_order_id' => $newOrderId,
            'new_order_number' => $newOrderNumber,
        ];
    }

    /**
     * Construye el array products[] esperado por la API a partir de order_details.
     */
    protected function buildProductsFromOrder($order)
    {
        $products = [];
        $orderDetails = $order->order_details ?? $order->orderDetails ?? [];
        if (!is_array($orderDetails)) {
            $orderDetails = [];
        }
        foreach ($orderDetails as $d) {
            $detail = is_object($d) ? $d : (object) $d;
            $product = $detail->product ?? null;
            $productId = $detail->product_id ?? null;
            if (!$productId && $product) {
                $productId = $product->pro_id ?? $product->product_id ?? null;
            }
            if (!$productId) {
                continue;
            }
            $qty = (float) ($detail->qty ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $price = (float) ($detail->sale_price ?? 0);
            if ($price < 0) {
                $price = 0;
            }
            $products[] = [
                'pro_id' => (int) $productId,
                'qty' => $qty,
                'price' => $price,
                'fixed_price' => !empty($detail->fixed_price) || !empty($detail->fixed_pricedate),
                'comment' => trim((string) ($detail->comments ?? $detail->comment ?? '')),
            ];
        }
        return $products;
    }

    protected function extractInternalNotes($order)
    {
        $notes = $order->notes ?? [];
        if (!is_array($notes)) {
            return (string) ($order->internal_notes ?? $order->internal_comments ?? '');
        }
        foreach ($notes as $note) {
            $n = is_object($note) ? $note : (object) $note;
            if (isset($n->type) && (string) $n->type === 'internal' && isset($n->note)) {
                return trim((string) $n->note);
            }
        }
        return (string) ($order->internal_notes ?? $order->internal_comments ?? '');
    }
}
