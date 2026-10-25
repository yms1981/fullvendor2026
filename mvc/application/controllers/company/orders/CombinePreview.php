<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Vista previa de combinación de órdenes.
 * POST order_ids[] → Valida reglas y devuelve JSON con datos para el modal de confirmación.
 * 
 * Reglas:
 * 1. Todas las órdenes deben ser del mismo cliente
 * 2. Solo se permiten órdenes en estado New (0) o Read (1)
 *    NO se pueden combinar órdenes en estado Approved (8) o Warehouse Completed (11)
 */
class CombinePreview extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
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
        $invalidStatusNames = [];
        $errors = [];

        foreach ($orderIds as $orderId) {
            $response = $this->getObjectFromApi("order/{$orderId}/full");
            if ($this->hasError($response) || !$response) {
                $errors[] = $this->langArr['order_not_found'] ?? 'Orden no encontrada.';
                continue;
            }
            $order = isset($response->data) ? $response->data : $response;
            $orders[] = $order;

            // Validar estado (solo cod_status 0 o 1). API expone order_status = cod_status.
            $codStatus = (int) ($order->order_status ?? $order->cod_status ?? (isset($order->statusOrder->cod_status) ? (int) $order->statusOrder->cod_status : 0));
            if (!in_array($codStatus, $allowedStatuses, true)) {
                $name = $statusNameMap[$codStatus] ?? $order->statusOrder->name_status_english ?? $order->statusOrder->name_status_spanish ?? (string) $codStatus;
                $invalidStatusNames[$name] = true;
            }

            // Validar mismo cliente
            $orderCustomerId = (int) ($order->customer_id ?? 0);
            if ($customerId === null) {
                $customerId = $orderCustomerId;
                $customerData = $order->customer ?? null;
            } elseif ($customerId !== $orderCustomerId) {
                $errors[] = $this->langArr['combine_different_customers'] ?? 'Las órdenes deben ser del mismo cliente.';
            }
        }

        if (!empty($invalidStatusNames)) {
            $invalidList = implode(', ', array_keys($invalidStatusNames));
            $allowedList = implode(' ' . ($this->langArr['or'] ?? 'o') . ' ', $allowedNames);
            $msg = sprintf(
                $this->langArr['combine_invalid_status_message'] ?? 'Solo se permiten órdenes en estado %s. Las órdenes seleccionadas tienen otros estados (ej.: %s).',
                $allowedList,
                $invalidList
            );
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $msg,
                'errors' => [$msg],
            ]));
            return;
        }

        if (!empty($errors)) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => implode(' ', array_unique($errors)),
                'errors' => array_unique($errors),
            ]));
            return;
        }

        // Consolidar items
        $consolidatedItems = [];
        $ordersSummary = [];
        $totalGeneral = 0;

        foreach ($orders as $order) {
            $orderTotal = 0;
            $details = $order->order_details ?? $order->orderDetails ?? [];
            
            foreach ($details as $detail) {
                $productId = (int) ($detail->product_id ?? 0);
                $qty = (float) ($detail->qty ?? 0);
                $price = (float) ($detail->sale_price ?? $detail->price ?? 0);
                $lineTotal = $qty * $price;
                $orderTotal += $lineTotal;

                $product = $detail->product ?? null;
                $productName = '';
                $sku = '';
                if ($product) {
                    $productName = $product->name ?? $product->descriptions ?? '';
                    $sku = $product->sku ?? '';
                }

                if (!isset($consolidatedItems[$productId])) {
                    $consolidatedItems[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'sku' => $sku,
                        'total_qty' => 0,
                        'total_amount' => 0,
                        'prices' => [],
                    ];
                }

                $consolidatedItems[$productId]['total_qty'] += $qty;
                $consolidatedItems[$productId]['total_amount'] += $lineTotal;
                $consolidatedItems[$productId]['prices'][] = ['qty' => $qty, 'price' => $price];
            }

            // Aplicar descuento de la orden
            $discount = (float) ($order->discount ?? 0);
            $discountType = (int) ($order->discount_type ?? 0);
            $discountAmount = 0;
            if ($discountType === 1 && $discount > 0) {
                $discountAmount = $orderTotal * ($discount / 100);
            } elseif ($discountType === 2 && $discount > 0) {
                $discountAmount = $discount;
            }
            $orderTotalFinal = $orderTotal - $discountAmount;
            $totalGeneral += $orderTotalFinal;

            $ordersSummary[] = [
                'order_id' => $order->order_id,
                'order_number' => $order->order_number ?? '',
                'subtotal' => round($orderTotal, 2),
                'discount' => round($discountAmount, 2),
                'total' => round($orderTotalFinal, 2),
            ];
        }

        // Calcular precio ponderado para productos con múltiples precios
        $finalItems = [];
        foreach ($consolidatedItems as $item) {
            $weightedPrice = $item['total_qty'] > 0 
                ? round($item['total_amount'] / $item['total_qty'], 2) 
                : 0;
            $finalItems[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'sku' => $item['sku'],
                'qty' => $item['total_qty'],
                'price' => $weightedPrice,
                'total' => round($item['total_amount'], 2),
            ];
        }

        // Datos del cliente
        $customer = null;
        if ($customerData) {
            $customer = [
                'customer_id' => $customerData->customer_id ?? $customerId,
                'name' => $customerData->name ?? '',
                'business_name' => $customerData->business_name ?? '',
                'email' => $customerData->email ?? '',
                'phone' => $customerData->phone ?? '',
                'cell_phone' => $customerData->cell_phone ?? '',
            ];
        }

        $this->output->set_output(json_encode([
            'success' => true,
            'customer' => $customer,
            'orders' => $ordersSummary,
            'consolidated_items' => $finalItems,
            'total' => round($totalGeneral, 2),
            'order_ids' => $orderIds,
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
