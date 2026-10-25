<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Importación de órdenes desde JSON (formato API order/import e import-bulk).
 * Preview → confirmación → llama a POST api/v2/order/import por cada orden.
 */
class ImportJson extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $langId = $this->getUserData('language_id');
        $this->langArr = $this->langmodel->getMdl('cmp-orders', $langId);
        if (! is_array($this->langArr)) {
            $this->langArr = $this->getModuleLang('cmp-orders') ?: [];
        }
        if (empty($this->langArr) && method_exists($this, 'getLangJsonSection')) {
            $this->langArr = $this->getLangJsonSection('cmp-orders');
        }
    }

    public function sample()
    {
        $companyId = (int) $this->getCompanyId();
        $example = [
            [
                'company_id' => $companyId > 0 ? $companyId : 1,
                'cabezal' => [
                    'iderp' => '18568',
                    'fecha' => date('Y-m-d'),
                    'norden' => 'ERP-1001',
                    'notas' => 'Pedido ERP',
                    'descuento' => 0,
                    'subtotal' => 100,
                    'total' => 100,
                    'customer_business_name' => 'ACME INC',
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@acme.com',
                    'seller_name' => 'Seller Name',
                    'warehouse_name' => 'Warehouse Name',
                ],
                'items' => [
                    [
                        'sku' => 'ABC-001',
                        'cantidad' => 2,
                        'precio' => 50,
                        'name' => 'Producto ABC',
                        'categories' => ['General'],
                        'stock' => 100,
                    ],
                ],
            ],
        ];

        $json = json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="orders-import-example.json"')
            ->set_output($json);
    }

    public function preview()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (! $this->isPost()) {
            $this->jsonOut(['success' => false, 'message' => $this->t('invalid_request', 'Invalid request')]);

            return;
        }

        $parsed = $this->readUploadedOrders();
        if (! $parsed['ok']) {
            $this->jsonOut(['success' => false, 'message' => $parsed['message']]);

            return;
        }

        $companyId = (int) $this->getCompanyId();
        $rows = [];
        $ok = 0;
        $warn = 0;
        $error = 0;
        $importable = 0;

        foreach ($parsed['orders'] as $index => $order) {
            $row = $this->buildPreviewRow($order, $index, $companyId);
            $rows[] = $row;
            if ($row['status'] === 'error') {
                $error++;
            } elseif ($row['status'] === 'warn') {
                $warn++;
                $importable++;
            } else {
                $ok++;
                $importable++;
            }
        }

        $this->jsonOut([
            'success' => true,
            'summary' => [
                'total' => count($rows),
                'ok' => $ok,
                'warn' => $warn,
                'error' => $error,
                'importable' => $importable,
            ],
            'rows' => $rows,
        ]);
    }

    public function confirm()
    {
        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        if (! $this->isPost()) {
            $this->jsonOut(['success' => false, 'message' => $this->t('invalid_request', 'Invalid request')]);

            return;
        }

        $parsed = $this->readUploadedOrders();
        if (! $parsed['ok']) {
            $this->jsonOut(['success' => false, 'message' => $parsed['message']]);

            return;
        }

        $companyId = (int) $this->getCompanyId();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $results = [];

        foreach ($parsed['orders'] as $index => $order) {
            $preview = $this->buildPreviewRow($order, $index, $companyId);
            if ($preview['status'] === 'error') {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'norden' => $preview['norden'],
                    'outcome' => 'failed',
                    'message' => $preview['message'],
                ];
                continue;
            }

            $payload = $this->normalizeOrderForApi($order, $companyId);
            try {
                $resp = $this->apiRequest('order/import', 'POST', [
                    'json' => $payload,
                    'timeout' => 180.0,
                    'connect_timeout' => 15.0,
                ], true);

                if (! is_array($resp) || ! empty($resp['error'])) {
                    $failed++;
                    $msg = is_array($resp['message'] ?? null)
                        ? (string) ($resp['message']['message'] ?? json_encode($resp['message']))
                        : (string) ($resp['message'] ?? $this->t('import_json_import_failed', 'Import failed'));
                    $results[] = [
                        'index' => $index,
                        'norden' => $preview['norden'],
                        'outcome' => 'failed',
                        'message' => $msg,
                    ];
                    continue;
                }

                if (! empty($resp['updated'])) {
                    $updated++;
                    $outcome = 'updated';
                } elseif (! empty($resp['skipped'])) {
                    $skipped++;
                    $outcome = 'skipped';
                } else {
                    $created++;
                    $outcome = 'created';
                }

                $results[] = [
                    'index' => $index,
                    'norden' => $preview['norden'],
                    'outcome' => $outcome,
                    'message' => (string) ($resp['message'] ?? ''),
                ];
            } catch (\Throwable $e) {
                $failed++;
                log_message('error', 'ImportJson::confirm '.$e->getMessage());
                $results[] = [
                    'index' => $index,
                    'norden' => $preview['norden'],
                    'outcome' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $this->jsonOut([
            'success' => ($created + $updated) > 0 || $failed === 0,
            'message' => $this->t('import_json_done', 'Importación finalizada.'),
            'summary' => [
                'total' => count($parsed['orders']),
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
            'results' => $results,
        ]);
    }

    private function isPost(): bool
    {
        return strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';
    }

    private function t(string $key, string $fallback = ''): string
    {
        $v = $this->langArr[$key] ?? $fallback;

        return is_string($v) && $v !== '' ? $v : $fallback;
    }

    private function jsonOut(array $payload): void
    {
        $this->output->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{ok:true,orders:list<array>}|array{ok:false,message:string}
     */
    private function readUploadedOrders(): array
    {
        $file = $_FILES['file_json'] ?? null;
        if (! is_array($file) || empty($file['tmp_name']) || ! is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'message' => $this->t('import_json_must_select', 'Seleccione un archivo JSON.')];
        }

        $name = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            return ['ok' => false, 'message' => $this->t('import_json_invalid_file', 'El archivo debe ser .json')];
        }

        $raw = @file_get_contents((string) $file['tmp_name']);
        if ($raw === false || trim($raw) === '') {
            return ['ok' => false, 'message' => $this->t('import_json_empty', 'El archivo JSON está vacío.')];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return ['ok' => false, 'message' => $this->t('import_json_parse_error', 'JSON inválido.')];
        }

        if (isset($decoded['cabezal']) || isset($decoded['header']) || isset($decoded['items'])) {
            $orders = [$decoded];
        } elseif (isset($decoded['orders']) && is_array($decoded['orders'])) {
            $orders = $decoded['orders'];
        } elseif ($this->isListArray($decoded)) {
            $orders = $decoded;
        } else {
            return ['ok' => false, 'message' => $this->t('import_json_format_error', 'Formato no reconocido. Use un objeto orden, un arreglo de órdenes o {orders:[...]}.')];
        }

        $clean = [];
        foreach ($orders as $order) {
            if (is_array($order)) {
                $clean[] = $order;
            }
        }

        if ($clean === []) {
            return ['ok' => false, 'message' => $this->t('import_json_no_orders', 'No se encontraron órdenes en el archivo.')];
        }

        if (count($clean) > 500) {
            return ['ok' => false, 'message' => $this->t('import_json_max_orders', 'Máximo 500 órdenes por archivo.')];
        }

        return ['ok' => true, 'orders' => $clean];
    }

    /**
     * Compatible con PHP 7.4 (array_is_list es 8.1+).
     */
    private function isListArray(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i) {
                return false;
            }
            $i++;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    private function buildPreviewRow(array $order, int $index, int $companyId): array
    {
        $cabezal = $order['cabezal'] ?? $order['header'] ?? [];
        if (! is_array($cabezal)) {
            $cabezal = [];
        }
        $items = $order['items'] ?? $order['productos'] ?? $order['lines'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }

        $norden = trim((string) ($cabezal['norden'] ?? $cabezal['order_number'] ?? ''));
        $iderp = trim((string) ($cabezal['iderp'] ?? $cabezal['cust_id_kor'] ?? ''));
        $customer = trim((string) (
            $cabezal['customer_business_name']
            ?? $cabezal['business_name']
            ?? $cabezal['customer_name']
            ?? $cabezal['name']
            ?? ''
        ));
        $total = $cabezal['total'] ?? $cabezal['total_amount'] ?? null;
        $validItems = 0;
        $calcTotal = 0.0;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $qty = (float) ($item['cantidad'] ?? $item['qty'] ?? $item['quantity'] ?? 0);
            $price = (float) ($item['precio'] ?? $item['price'] ?? $item['sale_price'] ?? 0);
            if ($qty > 0 && $price > 0) {
                $validItems++;
                $calcTotal += $qty * $price;
            }
        }
        if ($total === null || $total === '') {
            $total = $calcTotal;
        }

        $status = 'ok';
        $message = '';
        if ($norden === '' || $iderp === '') {
            $status = 'error';
            $message = $this->t('import_json_missing_keys', 'Faltan norden o iderp en el cabezal.');
        } elseif ($validItems < 1) {
            $status = 'error';
            $message = $this->t('import_json_empty_items', 'La orden no tiene ítems válidos.');
        } elseif ($customer === '') {
            $status = 'warn';
            $message = $this->t('import_json_customer_warn', 'Sin nombre de cliente: se usará si el iderp ya existe.');
        }

        $orderCompany = (int) ($order['company_id'] ?? $cabezal['company_id'] ?? 0);
        if ($orderCompany > 0 && $companyId > 0 && $orderCompany !== $companyId) {
            $status = 'warn';
            $message = trim($message.' '.$this->t('import_json_company_override', 'Se usará la compañía de la sesión.'));
        }

        return [
            'index' => $index,
            'norden' => $norden,
            'iderp' => $iderp,
            'customer' => $customer,
            'items_count' => $validItems,
            'total' => (float) $total,
            'status' => $status,
            'message' => trim($message),
        ];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    private function normalizeOrderForApi(array $order, int $companyId): array
    {
        $order['company_id'] = $companyId;
        if (isset($order['cabezal']) && is_array($order['cabezal'])) {
            $order['cabezal']['company_id'] = $companyId;
        }
        if (isset($order['header']) && is_array($order['header']) && ! isset($order['cabezal'])) {
            $order['cabezal'] = $order['header'];
            unset($order['header']);
            $order['cabezal']['company_id'] = $companyId;
        }

        return $order;
    }
}
