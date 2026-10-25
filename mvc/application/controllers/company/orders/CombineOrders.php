<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Combina múltiples órdenes en una nueva orden.
 * POST order_ids[] → Valida, consolida y crea una nueva orden.
 * 
 * Reglas:
 * 1. Todas las órdenes deben ser del mismo cliente
 * 2. Solo se permiten órdenes en estado New (0) o Read (1)
 *    NO se pueden combinar órdenes en estado Approved (8) o Warehouse Completed (11)
 * 3. Productos repetidos con diferentes precios: se pondera el precio
 */
class CombineOrders extends Company_Controller
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

        if (count($orderIds) < 2) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $this->langArr['combine_min_orders'] ?? 'Debe seleccionar al menos 2 órdenes para combinar.',
            ]));
            return;
        }

        $statusNameMap = $this->getStatusNameMap();
        // Solo permitir New (0 Placed) y Read (1). NO se permiten Approved (8) ni Warehouse Completed (11)
        $allowedStatuses = [0, 1];
        $allowedNames = array_filter(array_map(function ($cod) use ($statusNameMap) {
            return $statusNameMap[$cod] ?? null;
        }, $allowedStatuses));
        if (empty($allowedNames)) {
            $allowedNames = ['New', 'Read'];
        }

        // Obtener datos de las órdenes
        $orders = [];
        $customerId = null;
        $customerData = null;
        $firstOrder = null;

        foreach ($orderIds as $orderId) {
            $response = $this->getObjectFromApi("order/{$orderId}/full");
            if ($this->hasError($response) || !$response) {
                $this->output->set_output(json_encode([
                    'success' => false,
                    'message' => $this->langArr['order_not_found'] ?? 'Orden no encontrada.',
                ]));
                return;
            }
            $order = isset($response->data) ? $response->data : $response;
            $orders[] = $order;

            if ($firstOrder === null) {
                $firstOrder = $order;
            }

            // Validar estado (cod_status). API expone order_status = cod_status.
            $codStatus = (int) ($order->order_status ?? $order->cod_status ?? (isset($order->statusOrder->cod_status) ? (int) $order->statusOrder->cod_status : 0));
            if (!in_array($codStatus, $allowedStatuses, true)) {
                $invalidName = $statusNameMap[$codStatus] ?? $order->statusOrder->name_status_english ?? $order->statusOrder->name_status_spanish ?? (string) $codStatus;
                $allowedList = implode(' ' . ($this->langArr['or'] ?? 'o') . ' ', $allowedNames);
                $msg = sprintf(
                    $this->langArr['combine_invalid_status_message'] ?? 'Solo se permiten órdenes en estado %s. Las órdenes seleccionadas tienen otros estados (ej.: %s).',
                    $allowedList,
                    $invalidName
                );
                $this->output->set_output(json_encode([
                    'success' => false,
                    'message' => $msg,
                ]));
                return;
            }

            // Validar mismo cliente
            $orderCustomerId = (int) ($order->customer_id ?? 0);
            if ($customerId === null) {
                $customerId = $orderCustomerId;
                $customerData = $order->customer ?? null;
            } elseif ($customerId !== $orderCustomerId) {
                $this->output->set_output(json_encode([
                    'success' => false,
                    'message' => $this->langArr['combine_different_customers'] ?? 'Las órdenes deben ser del mismo cliente.',
                ]));
                return;
            }
        }

        // Consolidar items con precio ponderado
        $consolidatedItems = [];
        $totalDiscount = 0;

        foreach ($orders as $order) {
            $details = $order->order_details ?? $order->orderDetails ?? [];
            $orderSubtotal = 0;
            
            foreach ($details as $detail) {
                $productId = (int) ($detail->product_id ?? 0);
                $qty = (float) ($detail->qty ?? 0);
                $price = (float) ($detail->sale_price ?? $detail->price ?? 0);
                $lineTotal = $qty * $price;
                $orderSubtotal += $lineTotal;

                if (!isset($consolidatedItems[$productId])) {
                    $consolidatedItems[$productId] = [
                        'product_id' => $productId,
                        'total_qty' => 0,
                        'total_amount' => 0,
                    ];
                }

                $consolidatedItems[$productId]['total_qty'] += $qty;
                $consolidatedItems[$productId]['total_amount'] += $lineTotal;
            }

            // Acumular descuentos
            $discount = (float) ($order->discount ?? 0);
            $discountType = (int) ($order->discount_type ?? 0);
            if ($discountType === 1 && $discount > 0) {
                $totalDiscount += $orderSubtotal * ($discount / 100);
            } elseif ($discountType === 2 && $discount > 0) {
                $totalDiscount += $discount;
            }
        }

        // La API store espera: products (pro_id, qty, price, fixed_price, comment), seller_id, customer_id, discount_type, discount
        $products = [];
        foreach ($consolidatedItems as $item) {
            $weightedPrice = $item['total_qty'] > 0
                ? round($item['total_amount'] / $item['total_qty'], 4)
                : 0;
            $products[] = [
                'pro_id' => $item['product_id'],
                'qty' => $item['total_qty'],
                'price' => $weightedPrice,
                'fixed_price' => false,
                'comment' => '-',
            ];
        }

        $sellerId = (int) ($firstOrder->seller_id ?? 0);
        if ($sellerId === 0) {
            $vendor = $this->getDataFromApi('company/' . $this->getCompanyId() . '/sellers');
            $userId = $this->getUserData('id');
            $sellers = is_array($vendor) ? ($vendor['data'] ?? $vendor) : (isset($vendor->data) ? $vendor->data : []);
            if (is_object($sellers)) {
                $sellers = (array) $sellers;
            }
            foreach (is_array($sellers) ? $sellers : [] as $s) {
                if ((int) ($s->user_id ?? $s['user_id'] ?? 0) === (int) $userId) {
                    $sellerId = (int) ($s->id ?? $s['id'] ?? 0);
                    break;
                }
            }
        }

        $newOrderData = [
            'customer_id' => $customerId,
            'seller_id' => $sellerId,
            'discount' => round($totalDiscount, 2),
            'discount_type' => 2, // Monto fijo
            'proforma' => false,
            'internal_comments' => sprintf(
                $this->langArr['combine_comment'] ?? 'Orden combinada de: %s',
                implode(', ', array_map(function ($o) { return $o->order_number ?? $o->order_id; }, $orders))
            ),
            'products' => $products,
        ];

        $createResponse = $this->post('order', $newOrderData);

        if ($this->hasError($createResponse)) {
            $errorMsg = $this->langArr['combine_error'] ?? 'Error al combinar órdenes.';
            if (is_object($createResponse) && isset($createResponse->message)) {
                $errorMsg = $createResponse->message;
            } elseif (is_array($createResponse) && isset($createResponse['message'])) {
                $errorMsg = $createResponse['message'];
            }
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $errorMsg,
            ]));
            return;
        }

        // Respuesta puede ser { data: { order_id, order_number } } o directo { order_id, order_number }
        $payload = is_object($createResponse) ? $createResponse : (object) $createResponse;
        $data = $payload->data ?? $payload;
        $newOrderId = $data->order_id ?? $data->id ?? (is_array($data) ? ($data['order_id'] ?? $data['id'] ?? null) : null);
        $newOrderNumber = $data->order_number ?? (is_array($data) ? ($data['order_number'] ?? null) : null);

        if (!$newOrderId && !$newOrderNumber) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => is_object($createResponse) ? ($createResponse->message ?? $this->langArr['combine_error'] ?? 'Error al combinar órdenes.') : ($createResponse['message'] ?? $this->langArr['combine_error'] ?? 'Error al combinar órdenes.'),
            ]));
            return;
        }

        // Cancelar las órdenes originales (cod_status 7 = Canceled)
        foreach ($orderIds as $orderId) {
            $this->patch("order/{$orderId}/status", ['cod_status' => 7]);
        }

        $this->output->set_output(json_encode([
            'success' => true,
            'message' => sprintf(
                $this->langArr['combine_success'] ?? 'Se combinaron %d órdenes en la orden #%s.',
                count($orderIds),
                $newOrderNumber ?? $newOrderId
            ),
            'new_order_id' => $newOrderId,
            'new_order_number' => $newOrderNumber,
        ]));
    }

    /**
     * Mapa cod_status => nombre del estado según idioma del usuario (cmp-orders).
     * @return array<int, string>
     */
    private function getStatusNameMap()
    {
        $raw = $this->getObjectFromApi('statusorder/list');
        if ($this->hasError($raw)) {
            return [];
        }
        $list = is_array($raw) ? $raw : (isset($raw->data) ? $raw->data : []);
        if (!is_array($list)) {
            return [];
        }
        $map = [];
        $statusLangId = $this->resolveCompanyPanelLangId();
        foreach ($list as $status) {
            $status = is_object($status) ? $status : (object) $status;
            $codStatus = $status->cod_status ?? null;
            if ($codStatus === null && $codStatus !== '0') {
                continue;
            }
            $codInt = (int) $codStatus;
            $nameEn = trim((string) ($status->name_status_english ?? ''));
            $nameEs = trim((string) ($status->name_status_spanish ?? ''));
            $label = fv_order_status_label($statusLangId, $nameEn, $nameEs);
            $map[$codInt] = $label !== '' ? $label : (string) $codInt;
        }
        return $map;
    }
}
