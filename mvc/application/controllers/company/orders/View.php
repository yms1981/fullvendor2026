<?php

require_once APPPATH . 'controllers/company/orders/Form.php';

class View extends Form
{
    use IsAction;

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);
        $companyId = $this->getCompanyId();
        $dataForView['warehouse_assign_users'] = [];
        if ($companyId) {
            $whResp = $this->getObjectFromApi('company/' . $companyId . '/warehouse-users-for-order-assignment');
            if (is_object($whResp) && isset($whResp->data) && is_array($whResp->data)) {
                $dataForView['warehouse_assign_users'] = $whResp->data;
            } elseif (is_array($whResp) && isset($whResp['data']) && is_array($whResp['data'])) {
                $dataForView['warehouse_assign_users'] = $whResp['data'];
            }
        }
        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $this->langArr + $pageTexts;
        $dataForView['pageTexts'] = $pageTexts;

        return $dataForView;
    }

    /**
     * @return array<int, string>
     */
    protected function orderViewRelations(): array
    {
        return [
            'customer',
            'customer.sellers',
            'customer.customer_group',
            'customer.commercialAddresses',
            'customer.commercialAddresses.city',
            'customer.commercialAddresses.state',
            'customer.commercialAddresses.country',
            'company',
            'company.formatDate',
            'company.format_numeric',
            'company.currencyModel',
            'seller',
            'seller.user',
            'warehouse_user',
            'warehouse',
            'order_details',
            'order_details.product',
            'order_details.product.images',
            'order_details.product.default_image',
            'notes',
            'statusOrder',
            'order_status_histories',
            'order_status_histories.status',
            'order_status_histories.user',
            'address',
            'address.city',
            'address.state',
            'address.country',
        ];
    }

    public function run($order_id = null, $second = null)
    {
        if ($second !== null && ($order_id === 'view' || !is_numeric($order_id))) {
            $order_id = $second;
        }

        $response = $this->getObjectFromApi("{$this->entityPath}/{$order_id}/full");

        if ($response && $this->hasError($response)) {
            $response = $this->getObjectFromApi("{$this->entityPath}/{$order_id}", [
                'relations' => $this->orderViewRelations(),
            ]);
        }

        if (!$response) {
            show_404();
        }

        $hasOrderData = false;
        if ((is_object($response) && isset($response->order_id)) || (is_object($response) && isset($response->data) && is_object($response->data) && isset($response->data->order_id))) {
            $hasOrderData = true;
        }
        if ((is_array($response) && isset($response['order_id'])) || (is_array($response) && isset($response['data']) && isset($response['data']['order_id']))) {
            $hasOrderData = true;
        }

        if (!$hasOrderData && $this->hasError($response)) {
            log_message('error', 'Order view API error for order_id=' . $order_id . ' response=' . json_encode(is_object($response) ? (array) $response : $response));
            show_404();
        }

        $order = is_object($response) && isset($response->data) ? $response->data : $response;
        if (is_array($order)) {
            $order = (object) json_decode(json_encode($order), false);
        }
        $order = $this->normalizeOrderForView($order);

        $currentStatusId = isset($order->status_id) ? (int) $order->status_id : (isset($order->status_order->id_status) ? (int) $order->status_order->id_status : null);
        if ($currentStatusId === 1) {
            try {
                $this->patch("{$this->entityPath}/{$order_id}/status", ['status_id' => 2]);
            } catch (\Throwable $e) {
                log_message('error', 'Order View: could not set status to Read for order_id=' . $order_id . ': ' . $e->getMessage());
            }
        }

        $info = $this->getDataForView(null, []);
        $info['order'] = $order;
        $info['load_error'] = false;
        $info['lang_json'] = [
            'cmp_orders' => ['lang' => $this->langArr],
            'cmp_catalog' => ['lang' => $this->getModuleLang('cmp-catalog') ?: []],
        ];
        $orderStatusesRaw = $this->getObjectFromApi('statusorder/list');
        $info['orderStatuses'] = $this->normalizeAndDeduplicateOrderStatuses($orderStatusesRaw ?: []);

        $customersRaw = $this->getDataFromApi('customer/all', [
            'props' => ['customer_id', 'name', 'business_name', 'phone', 'cell_phone', 'commercial_address', 'commercial_country'],
        ]);
        $customersList = (is_array($customersRaw) && isset($customersRaw['data'])) ? $customersRaw['data'] : (is_array($customersRaw) ? $customersRaw : []);
        $currentOrderCustomerId = isset($order->customer_id) ? (string) $order->customer_id : ((isset($order->customer->customer_id) ? (string) $order->customer->customer_id : ''));
        if ($currentOrderCustomerId !== '' && count($customersList) > 0) {
            $current = null;
            $others = [];
            foreach ($customersList as $c) {
                $cid = is_object($c) ? ($c->customer_id ?? $c->id ?? '') : ($c['customer_id'] ?? $c['id'] ?? '');
                if ((string) $cid === $currentOrderCustomerId) {
                    $current = $c;
                } else {
                    $others[] = $c;
                }
            }
            $info['customers'] = $current !== null ? array_merge([$current], $others) : $customersList;
        } else {
            $info['customers'] = $customersList;
        }

        $info = $this->enrichOrderFormData($info, $order->customer ?? null);
        $langExport = $this->getModuleLang('cmp-modal-export-layout') ?: [];
        $info['pageTexts'] = ($this->langArr ?? []) + $langExport + ($info['pageTexts'] ?? []);
        $info['seller'] = $order->seller ?? null;
        $info['show_dispatched_info'] = $this->orderHasDispatchedInfo($order);
        $info['dispatched_warehouse_display'] = $this->resolveOrderWarehouseDisplay($order);

        $this->addJsFile('/companyasset/company/order/export.js');
        $this->addJsFile('/companyasset/company/order/ImagePopover.js');
        $this->showContent('company/order/order_detailsview.php', $info);
    }

    /**
     * True si la orden tiene despacho (cantidades delivered) o estado de despacho/almacén completado.
     */
    protected function orderHasDispatchedInfo($order): bool
    {
        if (!$order) {
            return false;
        }

        $cod = null;
        if (isset($order->status_order) && is_object($order->status_order) && isset($order->status_order->cod_status)) {
            $cod = (int) $order->status_order->cod_status;
        } elseif (isset($order->statusOrder) && is_object($order->statusOrder) && isset($order->statusOrder->cod_status)) {
            $cod = (int) $order->statusOrder->cod_status;
        } elseif (isset($order->order_status) && is_numeric($order->order_status)) {
            $cod = (int) $order->order_status;
        }

        // 3 Dispatched, 4 Delivered, 10 Partially Completed, 11 Warehouse Completed, 12 Completed
        if (in_array($cod, [3, 4, 10, 11, 12], true)) {
            return true;
        }

        $details = $order->order_details ?? $order->orderDetails ?? [];
        if (is_object($details) && method_exists($details, 'all')) {
            $details = $details->all();
        }
        if (!is_array($details)) {
            return false;
        }

        foreach ($details as $detail) {
            $d = is_array($detail) ? (object) $detail : $detail;
            $dq = (float) ($d->delivered_quantity ?? $d->delivered_qty ?? 0);
            $dp = $d->delivered_pack ?? null;
            if ($dq != 0.0 || ($dp !== null && $dp !== '' && (float) $dp != 0.0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nombre del almacenero / depósito asignado a la orden.
     */
    protected function resolveOrderWarehouseDisplay($order): ?string
    {
        if (!$order) {
            return null;
        }
        if (!empty($order->warehouse_user_display) && is_string($order->warehouse_user_display)) {
            return trim($order->warehouse_user_display) ?: null;
        }
        $wu = $order->warehouse_user ?? $order->warehouseUser ?? null;
        if (is_object($wu)) {
            if (!empty($wu->user) && is_object($wu->user)) {
                $nm = trim((string) ($wu->user->name ?? ''));
                if ($nm !== '') {
                    return $nm;
                }
                $em = trim((string) ($wu->user->email ?? ''));
                if ($em !== '') {
                    return $em;
                }
            }
            $fn = trim(((string) ($wu->first_name ?? '')) . ' ' . ((string) ($wu->last_name ?? '')));
            if ($fn !== '') {
                return $fn;
            }
        }
        if (!empty($order->warehouse) && is_object($order->warehouse) && !empty($order->warehouse->name)) {
            return trim((string) $order->warehouse->name) ?: null;
        }

        return null;
    }
}
