<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Income extends Company_Controller
{
    use IsAction;

    public function run($customer_id = null) {
        $customerIdFromUri = $this->uri->segment(4);
        $customerId = null;
        if (is_numeric((string) $customer_id) && (int) $customer_id > 0) {
            $customerId = (int) $customer_id;
        } elseif (is_numeric((string) $customerIdFromUri) && (int) $customerIdFromUri > 0) {
            $customerId = (int) $customerIdFromUri;
        }

        $customer = null;
        if ($customerId !== null) {
            $customer = $this->getObjectFromApi("customer/{$customerId}", [
                'props' => [
                    'customer_id',
                    'cust_id_kor',
                    'business_name',
                    'business_name_extended',
                    'name',
                    'email',
                    'phone',
                    'cell_phone',
                    'customer_status',
                    'group_id',
                ],
                'relations' => ['customer_group', 'sellers.user'],
            ]);
            if (!is_object($customer) || isset($customer->error)) {
                $customer = null;
            }
        }

        $customerGroup = null;
        if (isset($customer)) {
            $customerGroup = $customer->customer_group ?? $customer->customerGroup ?? null;
        }

        $customerData = [
            'customer_id' => $customer->customer_id ?? $customerId ?? '',
            'cust_id_kor' => $customer->cust_id_kor ?? '',
            'business_name' => $customer->business_name_extended ?? $customer->business_name ?? '',
            'contact' => $customer->name ?? $customer->contact_name ?? '',
            'email' => $customer->email ?? '',
            'phone' => $customer->phone ?? '',
            'cell_phone' => $customer->cell_phone ?? '',
            'group_name' => $customerGroup->name ?? '',
            'customer_status' => isset($customer->customer_status) ? (string) $customer->customer_status : '',
            'sellers' => [],
        ];

        if (isset($customer) && isset($customer->sellers) && (is_array($customer->sellers) || is_object($customer->sellers))) {
            foreach ((array) $customer->sellers as $seller) {
                if (isset($seller->user->name) && trim((string) $seller->user->name) !== '') {
                    $customerData['sellers'][] = (string) $seller->user->name;
                }
            }
        }

        $incomeTotals = $this->buildIncomeTotals($customerId);

        $this->showContent('company/customer/customer_income', [
            'pageTexts' => $this->getPageTexts(),
            'customerData' => $customerData,
            'monthlyRows' => $incomeTotals['monthlyRows'],
            'totorderquantity' => $incomeTotals['totorderquantity'],
            'avgsaleprice' => $incomeTotals['avgsaleprice'],
            'avgvaluepermonth' => $incomeTotals['avgvaluepermonth'],
            'totorderpricevalue' => $incomeTotals['totorderpricevalue'],
        ]);
    }

    /**
     * @return array{
     *   monthlyRows: array<int, array{month: string, value: float|int, quantity: float|int}>,
     *   totorderquantity: float|int,
     *   avgsaleprice: float|int,
     *   avgvaluepermonth: float|int,
     *   totorderpricevalue: float|int
     * }
     */
    private function buildIncomeTotals(?int $customerId): array
    {
        $empty = [
            'monthlyRows' => $this->buildEmptyMonthlyRows(),
            'totorderquantity' => 0,
            'avgsaleprice' => 0,
            'avgvaluepermonth' => 0,
            'totorderpricevalue' => 0,
        ];

        if ($customerId === null || $customerId <= 0) {
            return $empty;
        }

        $companyId = (int) $this->getCompanyId();
        $ordersRaw = $this->getObjectFromApi("order/customer/{$customerId}/payed", [
            'company_id' => $companyId,
        ]);

        $orders = $this->normalizeOrdersList($ordersRaw);
        if ($orders === []) {
            return $empty;
        }

        $totQty = 0.0;
        $totValue = 0.0;
        $orderCount = 0;
        $monthlyValue = [];
        $monthlyQty = [];

        foreach ($orders as $orderObj) {
            $tipoD = strtoupper(trim((string) ($orderObj->tipo_d ?? 'D')));
            if ($tipoD === 'I') {
                continue;
            }

            $statusCod = null;
            if (isset($orderObj->status_order->cod_status)) {
                $statusCod = (int) $orderObj->status_order->cod_status;
            } elseif (isset($orderObj->statusOrder->cod_status)) {
                $statusCod = (int) $orderObj->statusOrder->cod_status;
            }
            if ($statusCod === -1) {
                continue;
            }

            $details = $orderObj->order_details ?? $orderObj->orderDetails ?? [];
            if (!is_array($details) && !is_object($details)) {
                $details = [];
            }

            $orderQty = 0.0;
            $orderLineValue = 0.0;
            foreach ((array) $details as $detail) {
                if (!is_object($detail) && !is_array($detail)) {
                    continue;
                }
                $detailObj = is_array($detail) ? (object) $detail : $detail;
                $qty = (float) ($detailObj->qty ?? 0);
                $salePrice = (float) ($detailObj->sale_price ?? 0);
                if ($tipoD === 'D' && $qty < 0) {
                    $qty = abs($qty);
                }
                $orderQty += $qty;
                $orderLineValue += $qty * $salePrice;
            }

            // Preferir total de la orden (incluye descuentos) si viene en la API.
            $orderTotal = null;
            if (isset($orderObj->total) && is_numeric($orderObj->total)) {
                $orderTotal = (float) $orderObj->total;
            } elseif (isset($orderObj->total_amount) && is_numeric($orderObj->total_amount)) {
                $orderTotal = (float) $orderObj->total_amount;
            }
            if ($orderTotal === null) {
                $orderTotal = $orderLineValue;
            }
            if ($tipoD === 'D' && $orderTotal < 0) {
                $orderTotal = abs($orderTotal);
            }

            $createdRaw = (string) ($orderObj->created ?? '');
            $monthKey = '';
            if ($createdRaw !== '') {
                $ts = strtotime($createdRaw);
                if ($ts !== false) {
                    $monthKey = date('m-Y', $ts);
                }
            }

            $orderCount++;
            $totQty += $orderQty;
            $totValue += $orderTotal;

            if ($monthKey !== '') {
                if (!isset($monthlyValue[$monthKey])) {
                    $monthlyValue[$monthKey] = 0.0;
                    $monthlyQty[$monthKey] = 0.0;
                }
                $monthlyValue[$monthKey] += $orderTotal;
                $monthlyQty[$monthKey] += $orderQty;
            }
        }

        $avgsaleprice = ($orderCount > 0) ? round($totValue / $orderCount) : 0;
        $monthCountWithSales = count(array_filter($monthlyValue, static fn ($v) => (float) $v != 0.0));
        $avgvaluepermonth = $monthCountWithSales > 0
            ? round($totValue / $monthCountWithSales)
            : 0;

        return [
            'monthlyRows' => $this->buildMonthlyRows($monthlyValue, $monthlyQty),
            'totorderquantity' => round($totQty),
            'avgsaleprice' => $avgsaleprice,
            'avgvaluepermonth' => $avgvaluepermonth,
            'totorderpricevalue' => round($totValue),
        ];
    }

    /**
     * @param mixed $ordersRaw
     * @return list<object>
     */
    private function normalizeOrdersList($ordersRaw): array
    {
        if (is_array($ordersRaw) && isset($ordersRaw['error'])) {
            return [];
        }
        if (is_object($ordersRaw) && isset($ordersRaw->error)) {
            return [];
        }

        if (is_object($ordersRaw) && isset($ordersRaw->data) && (is_array($ordersRaw->data) || is_object($ordersRaw->data))) {
            $ordersRaw = $ordersRaw->data;
        } elseif (is_array($ordersRaw) && isset($ordersRaw['data']) && is_array($ordersRaw['data'])) {
            $ordersRaw = $ordersRaw['data'];
        }

        if (!is_array($ordersRaw) && !is_object($ordersRaw)) {
            return [];
        }

        $out = [];
        foreach ((array) $ordersRaw as $key => $order) {
            // Evitar meta/links de respuestas paginadas.
            if (is_string($key) && !is_numeric($key)) {
                continue;
            }
            if (!is_object($order) && !is_array($order)) {
                continue;
            }
            $orderObj = is_array($order) ? (object) $order : $order;
            if (!isset($orderObj->order_id) && !isset($orderObj->order_details) && !isset($orderObj->orderDetails)) {
                continue;
            }
            $out[] = $orderObj;
        }

        return $out;
    }

    /**
     * @param array<string, float> $monthlyValue
     * @param array<string, float> $monthlyQty
     * @return list<array{month: string, value: float|int, quantity: float|int}>
     */
    private function buildMonthlyRows(array $monthlyValue, array $monthlyQty): array
    {
        $months = [];
        for ($j = 0; $j < 6; $j++) {
            $months[] = date('m-Y', strtotime('-' . $j . ' month'));
        }
        sort($months);

        $rows = [];
        foreach ($months as $monthKey) {
            $rows[] = [
                'month' => str_replace('-', '/', $monthKey),
                'value' => round((float) ($monthlyValue[$monthKey] ?? 0)),
                'quantity' => round((float) ($monthlyQty[$monthKey] ?? 0)),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{month: string, value: int, quantity: int}>
     */
    private function buildEmptyMonthlyRows(): array
    {
        return $this->buildMonthlyRows([], []);
    }

    protected function getPageTexts(): array
    {
        $langCustomers = (array) ($this->getModuleLang('cmp-clientes') ?? []);
        $langProfile = (array) ($this->getModuleLang('cmp-perfil') ?? []);
        $langEnabled = (array) ($this->getModuleLang('cmp-enabled') ?? []);

        return [
            'title' => $langCustomers['view_income'] ?? '',
            'back' => $langProfile['back'] ?? '',
            'income_customer_info' => $langCustomers['income_customer_info'] ?? '',
            'income_customer_id' => $langCustomers['income_customer_id'] ?? '',
            'income_erp_id' => $langCustomers['field_cust_id_kor'] ?? '',
            'income_business_name' => $langCustomers['nombre_empresa'] ?? '',
            'income_contact' => $langCustomers['nombre'] ?? '',
            'income_email' => $langCustomers['email'] ?? '',
            'income_phone' => $langCustomers['phones'] ?? '',
            'income_cell_phone' => $langCustomers['col_cellphone'] ?? '',
            'income_group' => $langCustomers['group_customer'] ?? '',
            'income_sellers' => $langCustomers['vendedores'] ?? '',
            'income_customer_status' => $langCustomers['col_customer_status'] ?? '',
            'income_no_data' => $langCustomers['option_vacio'] ?? '',
            'income_month' => $langCustomers['income_month'] ?? '',
            'income_value' => $langCustomers['income_value'] ?? '',
            'income_quantity' => $langCustomers['income_quantity'] ?? '',
            'income_total_sales' => $langCustomers['income_total_sales'] ?? '',
            'income_total_average_sale' => $langCustomers['income_total_average_sale'] ?? '',
            'income_total_average_month' => $langCustomers['income_total_average_month'] ?? '',
            'income_total_amount' => $langCustomers['income_total_amount'] ?? '',
            'income_status_enabled' => $langEnabled['option_enable'] ?? '',
            'income_status_disabled' => $langEnabled['option_disable'] ?? '',
        ];
    }
}
