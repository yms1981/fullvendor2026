<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Historial de precios previos por producto/cliente (modal en detalle de orden).
 *
 * Datos vía API: order, catalogcart, previouslyOrderedProducts, statusorder/list, format-numeric.
 *
 * @see OrdenModel::previouslyOrderedProductsInAprovedOrders()
 */
class GetHistorial extends Company_Controller
{
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('OrdenModel');
    }

    public function run()
    {
        $request = $this->input->post();
        $companyId = (int) $this->getCompanyId();
        $langId = (int) ($this->getUserData('language_id') ?? 1);
        $langOrder = $this->getModuleLang('cmp-orders');

        $customerId = $this->resolveCustomerIdFromRequest($request, $companyId);

        if ($customerId === null || $customerId < 1 || empty($request['pro_id'])) {
            echo json_encode(['tbody' => '']);
            return;
        }

        $historial = $this->OrdenModel->previouslyOrderedProductsInAprovedOrders(
            $companyId,
            $customerId,
            $request['pro_id']
        );

        $statuslist = $this->fetchOrderStatusListForHistorial($langId, $langOrder);
        $formatNumeric = $this->getObjectFromApi("company/{$companyId}/format-numeric");
        $digits = (int) ($formatNumeric->digits ?? 2);
        $radix = $formatNumeric->radixPoint ?? '.';
        $group = $formatNumeric->groupSeparator ?? ',';

        $this->load->helper('fv_date');
        $title = $langOrder['copy_price'] ?? '';
        $pendingLabel = $langOrder['pending'] ?? '';
        $body = '';

        foreach ((array) $historial as $histRow) {
            if (!is_array($histRow) && !is_object($histRow)) {
                continue;
            }
            $hist = is_object($histRow) ? $histRow : (object) $histRow;
            if (isset($hist->error)) {
                continue;
            }
            $color = '';
            $nameStatus = '';
            $orderStatus = (int) ($hist->order_status ?? 0);
            if (in_array($orderStatus, array_column($statuslist, 'cod_status'), true)) {
                $index = array_search($orderStatus, array_column($statuslist, 'cod_status'), true);
                $color = $statuslist[$index]->color ?? '';
                $nameStatus = ($orderStatus === 9 && $pendingLabel !== '')
                    ? $pendingLabel
                    : ($statuslist[$index]->name_status ?? '');
            }

            $createdRaw = (string) ($hist->created ?? '');
            $created = function_exists('fv_format_display_date')
                ? fv_format_display_date($createdRaw)
                : fv_format_display_date($createdRaw !== '' ? $createdRaw : 'now');
            $price = number_format((float) ($hist->sale_price ?? 0), $digits, $radix, $group);
            $colorSafe = htmlspecialchars((string) $color, ENT_QUOTES, 'UTF-8');
            $nameSafe = htmlspecialchars((string) $nameStatus, ENT_QUOTES, 'UTF-8');
            $orderNumber = htmlspecialchars((string) ($hist->order_number ?? ''), ENT_QUOTES, 'UTF-8');
            $link = '<a href="' . company_url('orders/view/' . (int) ($hist->order_id ?? 0)) . '">' . $orderNumber . '</a>';

            $body .= '<tr>';
            $body .= '<td class="notranslate">' . htmlspecialchars($created, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '<td class="notranslate">' . $link . '</td>';
            $body .= '<td><span class="fv-sold-status"><span class="roundblock" style="background:' . $colorSafe . ';"></span><span>' . $nameSafe . '</span></span></td>';
            $body .= '<td class="text-right notranslate">$ ' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '<td class="text-right"><button type="button" title="' . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') . '" ';
            $body .= 'onclick="copy_saleprice()" data-copy-price="' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '" class="btn btn-success btn-sm"><i class="fa-solid fa-copy"></i></button></td>';
            $body .= '</tr>';
        }

        echo json_encode(['tbody' => $body]);
    }

    /**
     * @param array<string, mixed> $request
     */
    private function resolveCustomerIdFromRequest(array $request, int $companyId): ?int
    {
        if (! empty($request['order_id'])) {
            $order = $this->getObjectFromApi('order/' . (int) $request['order_id']);
            if (! $this->hasError($order) && $order && (int) ($order->company_id ?? 0) === $companyId) {
                $cid = (int) ($order->customer_id ?? 0);

                return $cid > 0 ? $cid : null;
            }

            return null;
        }

        if (! empty($request['cart_id'])) {
            $cartId = (int) $request['cart_id'];
            if ($cartId < 1) {
                return null;
            }
            $resp = $this->getObjectFromApi('catalogcart/' . $cartId);
            if ($this->hasError($resp)) {
                return null;
            }
            $cart = is_object($resp) && isset($resp->data) ? $resp->data : $resp;
            if (! is_object($cart) || (int) ($cart->company_id ?? 0) !== $companyId) {
                return null;
            }
            $cid = (int) ($cart->customer_id ?? 0);

            return $cid > 0 ? $cid : null;
        }

        return null;
    }

    /**
     * Lista de estados (cod_status, color, name_status) vía GET statusorder/list.
     *
     * @param array<string, string> $langOrder
     *
     * @return list<object{cod_status: int, color: string, name_status: string}>
     */
    private function fetchOrderStatusListForHistorial(int $langId, array $langOrder): array
    {
        $raw = $this->getObjectFromApi('statusorder/list');
        if ($this->hasError($raw)) {
            return [];
        }
        $list = is_array($raw) ? $raw : (isset($raw->data) && is_array($raw->data) ? $raw->data : []);
        if (! is_array($list)) {
            return [];
        }

        $statusLangKeys = [
            'New' => 'new', 'Read' => 'read', 'Pending' => 'pending', 'Dispatched' => 'dispatched',
            'Delivered' => 'delivered', 'Return Request' => 'return_request', 'Returned' => 'returned',
            'Canceled' => 'canceled', 'Approved' => 'approved', 'Warehouse Assigned' => 'warehouse_assigned',
            'Partially Completed' => 'partialy_completed', 'Warehouse Completed' => 'warehouse_completed',
            'Completed' => 'completed', 'Rejected' => 'rechazado', 'In Proccess' => 'pending',
            'Credit Note' => 'rechazado', 'Returned by Warehouse' => 'returned', 'Shopping' => 'new',
        ];

        $out = [];
        foreach ($list as $status) {
            $status = is_object($status) ? $status : (object) $status;
            $codStatus = $status->cod_status ?? null;
            if ($codStatus === null && $codStatus !== '0') {
                continue;
            }
            $codInt = (int) $codStatus;
            if ($codInt < 1) {
                continue;
            }
            $nameEn = (string) ($status->name_status_english ?? '');
            $key = $statusLangKeys[$nameEn] ?? strtolower(str_replace(' ', '_', $nameEn));
            $fromLang = $langOrder[$key] ?? '';
            if ($fromLang !== '') {
                $name = $fromLang;
            } elseif ($langId === 2) {
                $name = (string) ($status->name_status_spanish ?? $nameEn);
            } else {
                $name = $nameEn !== '' ? $nameEn : (string) $codInt;
            }
            $out[] = (object) [
                'cod_status' => $codInt,
                'color' => (string) ($status->color ?? ''),
                'name_status' => $name,
            ];
        }

        return $out;
    }
}
