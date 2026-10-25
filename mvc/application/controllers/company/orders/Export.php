<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Export extends Company_Controller
{
    use IsCRUD;
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function index()
    {
        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') !== 'POST') {
            show_404();
            return;
        }

        $post = $this->input->post();
        $orderIds = $this->extractOrderIds($post);
        $exportAll = ($post['input_todos'] ?? 'pagina') === 'todos';

        if (! $exportAll && $orderIds === []) {
            $this->jsonExportError($this->langArr['alert_select_at_least_one'] ?? '');
            return;
        }

        if ($exportAll) {
            $orderIds = $this->resolveAllOrderIds($post);
        }

        if ($orderIds === []) {
            $this->jsonExportError($this->langArr['invalid_request'] ?? '');
            return;
        }

        $rows = $this->buildImportCompatibleRows($orderIds);
        if ($rows === []) {
            $this->jsonExportError($this->langArr['export_order_no_lines'] ?? ($this->langArr['import_order_error_empty'] ?? ''));
            return;
        }

        $headers = [
            $this->langArr['import_order_col_sku'] ?? 'SKU',
            $this->langArr['import_order_col_product_name'] ?? '',
            $this->langArr['import_order_col_quantity'] ?? '',
            $this->langArr['import_order_col_sale_price'] ?? '',
        ];

        $this->sendConfigExcelDownload($headers, $rows, 'orders_' . date('Ymd') . '.xlsx');
    }

    private function jsonExportError(string $message): void
    {
        $this->output
            ->set_status_header(422)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false,
                'msg' => $message,
            ]));
    }

    /**
     * @param array<string, mixed> $post
     * @return list<int>
     */
    private function extractOrderIds(array $post): array
    {
        $raw = [];
        foreach ($post as $key => $value) {
            if (preg_match('/Ids$/', (string) $key) && is_array($value)) {
                $raw = array_merge($raw, $value);
            }
        }
        if (isset($post['order_ids']) && is_array($post['order_ids'])) {
            $raw = array_merge($raw, $post['order_ids']);
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed> $post
     * @return list<int>
     */
    private function resolveAllOrderIds(array $post): array
    {
        $params = array_merge(
            $this->buildApiFilterParams($post),
            [
                'company' => (int) $this->getCompanyId(),
                'all' => true,
                'props' => ['order_id'],
            ]
        );

        $response = $this->getObjectFromApi('order', $params);
        $items = $this->extractApiListItems($response);
        $ids = [];
        foreach ($items as $row) {
            $row = is_object($row) ? $row : (object) $row;
            $id = (int) ($row->order_id ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildApiFilterParams(array $post): array
    {
        $params = [];

        $search = trim((string) ($post['search'] ?? ''));
        if ($search !== '') {
            $params['search'] = $search;
        }

        $map = [
            'date_from' => 'date_from',
            'date_to' => 'date_to',
            'origin' => 'source',
            'groupId' => 'group',
        ];
        foreach ($map as $postKey => $apiKey) {
            $value = $post[$postKey] ?? null;
            if ($value !== null && $value !== '' && $value !== 'todo') {
                $params[$apiKey] = $value;
            }
        }

        if (empty($params['date_from'])) {
            $params['date_from'] = date('Y-m-d', strtotime('-30 days'));
        }
        if (empty($params['date_to'])) {
            $params['date_to'] = date('Y-m-d');
        }

        $sellerIds = $post['sellerId'] ?? $post['seller'] ?? null;
        if (is_array($sellerIds)) {
            $sellerIds = array_values(array_filter(array_map('intval', $sellerIds)));
            if ($sellerIds !== []) {
                $params['seller'] = $sellerIds;
            }
        }

        $status = $post['status'] ?? $post['status_cod'] ?? null;
        if (is_array($status)) {
            $status = array_values(array_filter(array_map('intval', $status)));
            if ($status !== []) {
                $params['status'] = $status;
            }
        }

        return $params;
    }

    /**
     * Filas compatibles con la plantilla de importación (SKU, nombre, cantidad, precio).
     *
     * @param list<int> $orderIds
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private function buildImportCompatibleRows(array $orderIds): array
    {
        $rows = [];
        foreach ($orderIds as $orderId) {
            $order = $this->fetchOrderWithDetails((int) $orderId);
            if ($order === null) {
                continue;
            }
            foreach ($this->orderDetailRows($order) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function fetchOrderWithDetails(int $orderId): ?object
    {
        if ($orderId < 1) {
            return null;
        }

        $order = $this->getObjectFromApi("order/{$orderId}/full");
        if (! is_object($order) || $this->hasError($order)) {
            $order = $this->getObjectFromApi("order/{$orderId}", [
                'relations' => ['order_details', 'order_details.product'],
            ]);
        }

        if (! is_object($order) || $this->hasError($order)) {
            return null;
        }

        return $order;
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private function orderDetailRows(object $order): array
    {
        $details = $order->order_details ?? $order->orderDetails ?? null;
        if ($details === null) {
            return [];
        }
        if (is_object($details) && ! ($details instanceof Traversable)) {
            $details = (array) $details;
        }
        if (! is_array($details) && ! ($details instanceof Traversable)) {
            return [];
        }

        $rows = [];
        foreach ($details as $detail) {
            $detail = is_object($detail) ? $detail : (object) $detail;
            $product = $detail->product ?? null;
            if (is_array($product)) {
                $product = (object) $product;
            }

            $sku = '';
            $name = '';
            if (is_object($product)) {
                $sku = trim((string) ($product->sku ?? ''));
                $name = trim((string) ($product->name ?? ''));
            }
            if ($sku === '') {
                $sku = trim((string) ($detail->sku ?? ''));
            }
            if ($name === '') {
                $name = trim((string) ($detail->name ?? $detail->product_name ?? ''));
            }

            $qty = $detail->qty ?? $detail->quantity ?? '';
            $price = $detail->sale_price ?? $detail->salePrice ?? $detail->price ?? '';

            if ($sku === '' && $name === '' && ($qty === '' || $qty === null) && ($price === '' || $price === null)) {
                continue;
            }

            $rows[] = [
                mb_strtoupper($sku, 'UTF-8'),
                $name,
                $this->formatExportNumber($qty),
                $this->formatExportNumber($price),
            ];
        }

        return $rows;
    }

    private function formatExportNumber($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (! is_numeric($value)) {
            return trim((string) $value);
        }

        $number = (float) $value;
        if (abs($number - round($number)) < 0.0000001) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }
}
