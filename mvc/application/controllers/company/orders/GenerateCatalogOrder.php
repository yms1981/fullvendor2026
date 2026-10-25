<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Modal "Agregar nueva orden" (catálogo, grilla o CSV).
 *
 * Migración (2026): sustituye company/order/generar_order_catalog en Order.php (CRUD legacy).
 * El menú y el listado usan /company/orders; este endpoint mantiene la misma lógica de negocio
 * hasta retirar por completo el controlador company/Order.php (fase 3 del plan de deprecación).
 *
 * @see docs/FLUJO_CREACION_ORDEN.md
 */
class GenerateCatalogOrder extends Company_Controller
{
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Helper');
    }

    public function run()
    {
        $request = $this->input->post();
        $compId = (int) $this->getCompanyId();
        $langId = $this->getUserData('language_id');
        $lang_order = $this->langmodel->getMdl('cmp-orders', $langId);
        if (!is_array($lang_order)) {
            $lang_order = [];
        }

        $type = null;
        if (isset($request['type_catalog']) && $request['type_catalog'] === 'catalog') {
            $type = 1;
        } elseif (isset($request['type_grid']) && $request['type_grid'] === 'grilla') {
            $type = 2;
        } elseif (isset($request['type_file']) && $request['type_file'] === 'csv') {
            $type = 3;
        }

        if ($type === 1) {
            $initError = $this->ensureOrderCatalogReady($compId, $lang_order);
            if ($initError !== null) {
                return $initError;
            }
        }

        if (!isset($request['customer_id']) || $request['customer_id'] === '') {
            return $this->Helper->response_json(['error' => $lang_order['debe_elegir_cliente'] ?? 'Debe elegir un cliente.']);
        }

        $customer = $this->getObjectFromApi("customer/{$request['customer_id']}");
        if ($customer === null || (is_array($customer) && isset($customer['error'])) || (is_object($customer) && isset($customer->error))) {
            return $this->Helper->response_json(['error' => $lang_order['cliente_no_encontrado'] ?? 'Cliente no encontrado.']);
        }

        $group_id = is_object($customer) ? ($customer->group_id ?? null) : ($customer['group_id'] ?? null);
        if ($group_id === null) {
            return $this->Helper->response_json(['error' => $lang_order['cliente_sin_grupo'] ?? 'Cliente sin grupo asignado.']);
        }
        $group_found = $this->getDataFromApi("customergroup/{$group_id}");

        if ($group_found == null) {
            if (!isset($request['not_confirm']) && !isset($request['confirm'])) {
                $msg = $lang_order['debe_verificar'];
                return $this->Helper->response_json(['success' => false, 'msg' => $msg, 'question_confirm' => true]);
            }

            if (isset($request['not_confirm'])) {
                $cid = is_object($customer) ? $customer->customer_id : ($customer['customer_id'] ?? null);
                return $this->Helper->response_json(
                    ['url_redirect' => company_url('customer/edit/' . $cid)]
                );
            }
        }

        $customer_id = is_object($customer) ? ($customer->customer_id ?? null) : ($customer['customer_id'] ?? null);

        switch ($type) {
            case 1:
                return $this->redirectToCatalogCart($request, $compId, $customer_id, $lang_order);
            case 2:
                $tipoD = isset($request['tipo_d']) && $request['tipo_d'] === 'C' ? 'C' : 'D';
                return $this->Helper->response_json(
                    ['url_redirect' => company_url('orders/add?customer=' . $request['customer_id'] . '&tipo_d=' . $tipoD)]
                );
            case 3:
                return $this->redirectFromCsvUpload($request, $lang_order);
        }

        if ($type === null) {
            return $this->Helper->response_json(['error' => $lang_order['seleccione_tipo_orden'] ?? 'Seleccione tipo de orden (catálogo, grilla o archivo).']);
        }

        return $this->Helper->response_json(['error' => $lang_order['tipo_no_soportado'] ?? 'Tipo de orden no reconocido.']);
    }

    private function redirectToCatalogCart(array $request, $compId, $customer_id, array $lang_order)
    {
        $customerId = (int) ($request['customer_id'] ?? $customer_id);
        if ($customerId < 1) {
            return $this->Helper->response_json([
                'error' => $lang_order['debe_elegir_cliente'] ?? '',
            ]);
        }

        $payload = [
            'customer_id' => $customerId,
            'company_id'  => (int) $compId,
        ];

        $seller = $this->getObjectFromApi("customer/{$customerId}/seller");
        if ($seller !== null && is_object($seller) && ! empty($seller->user_id)) {
            $payload['user_id'] = (int) $seller->user_id;
        } elseif (is_array($seller) && ! empty($seller['user_id'])) {
            $payload['user_id'] = (int) $seller['user_id'];
        } elseif ($seller === null || (is_array($seller) && isset($seller['error'])) || (is_object($seller) && isset($seller->error))) {
            return $this->Helper->response_json([
                'error' => $lang_order['vendedor_no_asignado'] ?? '',
            ]);
        }

        try {
            $resp = $this->post("company/{$compId}/order-catalog-session", $payload, true);
            $tokenKor = is_array($resp) ? ($resp['data']['token_kor'] ?? $resp['token_kor'] ?? null) : null;

            if ($this->hasError($resp) || empty($tokenKor)) {
                log_message('error', 'GenerateCatalogOrder order-catalog-session: ' . json_encode($resp));

                return $this->Helper->response_json([
                    'error' => $lang_order['error_crear_carrito_catalogo'] ?? '',
                ]);
            }

            $this->flash('loader', true);

            return $this->Helper->response_json([
                'url_redirect' => base_url('catalogo/producto?token=' . rawurlencode((string) $tokenKor)),
                'other_window' => true,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'GenerateCatalogOrder catalog: ' . $e->getMessage());

            return $this->Helper->response_json([
                'error' => $lang_order['error_crear_carrito_catalogo'] ?? '',
            ]);
        }
    }

    private function redirectFromCsvUpload(array $request, array $lang_order)
    {
        if (empty($_FILES['file_csv']['tmp_name']) || !is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
            return $this->Helper->response_json([
                'error' => $lang_order['add_order_select_csv'] ?? ($lang_order['import_no_file'] ?? ''),
            ]);
        }

        $this->load->library('Spreadsheet_import');
        if (!Spreadsheet_import::isAllowedImportFilename((string) ($_FILES['file_csv']['name'] ?? ''))) {
            return $this->Helper->response_json([
                'error' => $lang_order['import_invalid_file'] ?? '',
            ]);
        }

        $companyId = (int) $this->getCompanyId();
        $details = [];
        $errors = [];
        $line = 1;

        foreach (Spreadsheet_import::readRows($_FILES['file_csv']['tmp_name'], true) as $data) {
            $line++;
            $sku = isset($data[0]) ? trim((string) $data[0]) : '';
            $nameFile = isset($data[1]) ? trim((string) $data[1]) : '';
            $qtyRaw = isset($data[2]) ? trim((string) $data[2]) : '';
            $priceRaw = isset($data[3]) ? trim((string) $data[3]) : '';

            if ($sku === '' && $nameFile === '' && $qtyRaw === '' && $priceRaw === '') {
                continue;
            }

            if (count($data) < 4 || $sku === '') {
                $errors[] = sprintf($lang_order['import_order_error_line'] ?? 'Line %d: invalid row', $line);
                continue;
            }

            $qty = $this->parseImportNumber($qtyRaw);
            if ($qty === null || $qty <= 0) {
                $errors[] = sprintf($lang_order['import_order_error_line_qty'] ?? 'Line %d: invalid quantity', $line);
                continue;
            }

            $price = null;
            if ($priceRaw !== '') {
                $price = $this->parseImportNumber($priceRaw);
                if ($price === null || $price < 0) {
                    $errors[] = sprintf($lang_order['import_order_error_line_price'] ?? 'Line %d: invalid price', $line);
                    continue;
                }
            }

            $product = $this->findProductBySkuForImport($sku, $companyId);
            if ($product === null) {
                $errors[] = sprintf(
                    $lang_order['import_order_error_line_product'] ?? 'Line %d: product %s not found',
                    $line,
                    $sku
                );
                continue;
            }

            $productId = (int) ($product->pro_id ?? $product->product_id ?? 0);
            $resolvedName = trim((string) ($product->name ?? ''));
            $catalogPrice = (float) ($product->sale_price ?? $product->price ?? 0);
            $finalPrice = $price !== null ? $price : $catalogPrice;
            $img = $product->default_image ?? null;
            $imageUuid = '';
            if (is_object($img) && !empty($img->uuid)) {
                $imageUuid = (string) $img->uuid;
            } elseif (is_array($img) && !empty($img['uuid'])) {
                $imageUuid = (string) $img['uuid'];
            }

            $details[] = [
                'id' => '',
                'product_id' => $productId,
                'sku' => mb_strtoupper($sku, 'UTF-8'),
                'name' => $resolvedName !== '' ? $resolvedName : $nameFile,
                'qty' => $qty,
                'sale_price' => $finalPrice,
                'comments' => '',
                'fixed_price' => $price !== null ? 1 : 0,
                'image' => $imageUuid,
            ];
        }

        if ($errors !== []) {
            return $this->Helper->response_json([
                'error' => ($lang_order['import_order_preview_has_errors'] ?? '') . "\n" . implode("\n", array_slice($errors, 0, 8)),
            ]);
        }

        if ($details === []) {
            return $this->Helper->response_json([
                'error' => $lang_order['import_order_error_empty'] ?? '',
            ]);
        }

        $tipoD = isset($request['tipo_d']) && $request['tipo_d'] === 'C' ? 'C' : 'D';
        $payload = [
            'customer_id' => (int) ($request['customer_id'] ?? 0),
            'tipo_d' => $tipoD,
            'details' => $details,
        ];
        $query = http_build_query([
            'customer' => $payload['customer_id'],
            'tipo_d' => $tipoD,
            'import_detail' => json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
        $this->flash('loader', true);

        return $this->Helper->response_json([
            'url_redirect' => company_url('orders/add?' . $query),
        ]);
    }

    private function findProductBySkuForImport(string $sku, int $companyId): ?object
    {
        $sku = mb_strtoupper(trim($sku), 'UTF-8');
        if ($sku === '' || $companyId < 1) {
            return null;
        }

        $product = $this->getObjectFromApi('product/sku-' . rawurlencode($sku), [
            'company_id' => $companyId,
            'relations' => ['default_image'],
        ]);
        if ($product === null || $this->hasError($product)) {
            return null;
        }
        if (is_array($product)) {
            $product = (object) $product;
        }
        if (!is_object($product)) {
            return null;
        }
        $id = (int) ($product->pro_id ?? $product->product_id ?? 0);

        return $id > 0 ? $product : null;
    }

    private function parseImportNumber(string $raw): ?float
    {
        $normalized = str_replace([' ', "\xc2\xa0"], '', $raw);
        $normalized = str_replace(',', '.', $normalized);
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Categoría por defecto + catálogo FullVendor vía API (sin SQL directo en MVC).
     *
     * @return mixed|null Respuesta JSON de error o null si OK
     */
    private function ensureOrderCatalogReady(int $companyId, array $lang_order)
    {
        if ($companyId < 1) {
            return $this->Helper->response_json([
                'error' => $lang_order['error_config_catalog'] ?? '',
            ]);
        }

        try {
            $resp = $this->post("company/{$companyId}/prepare-order-catalog", ['company_id' => $companyId], true);
            if ($this->hasError($resp) || ! empty($resp['error'])) {
                log_message('error', 'GenerateCatalogOrder prepare-order-catalog: ' . json_encode($resp));

                return $this->Helper->response_json([
                    'error' => $lang_order['error_config_catalog'] ?? '',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'GenerateCatalogOrder init: ' . $e->getMessage());

            return $this->Helper->response_json([
                'error' => $lang_order['error_config_catalog'] ?? '',
            ]);
        }

        return null;
    }
}
