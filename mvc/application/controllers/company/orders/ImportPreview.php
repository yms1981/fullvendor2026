<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Preview + validación de planilla Excel para nueva orden.
 * POST file_csv → JSON con filas OK / error (producto inexistente, qty inválida, etc.).
 */
class ImportPreview extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $langId = $this->getUserData('language_id');
        $this->langArr = $this->langmodel->getMdl('cmp-orders', $langId);
        if (!is_array($this->langArr)) {
            $this->langArr = $this->getModuleLang('cmp-orders') ?: [];
        }
    }

    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $this->langArr['invalid_request'] ?? '',
            ]));
            return;
        }

        $file = $_FILES['file_csv'] ?? null;
        if (!is_array($file) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $this->langArr['import_no_file'] ?? ($this->langArr['add_order_select_csv'] ?? ''),
            ]));
            return;
        }

        $this->load->library('Spreadsheet_import');
        if (!Spreadsheet_import::isAllowedImportFilename((string) ($file['name'] ?? ''))) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => $this->langArr['import_invalid_file'] ?? '',
            ]));
            return;
        }

        $result = $this->validateSpreadsheetRows((string) $file['tmp_name']);
        $this->output->set_output(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{success:bool,message?:string,rows:list<array>,summary:array}
     */
    private function validateSpreadsheetRows(string $tmpPath): array
    {
        $companyId = (int) $this->getCompanyId();
        $rows = [];
        $valid = 0;
        $invalid = 0;
        $line = 1; // header = 1, data starts at 2

        foreach (Spreadsheet_import::readRows($tmpPath, true) as $data) {
            $line++;
            $sku = isset($data[0]) ? trim((string) $data[0]) : '';
            $nameFile = isset($data[1]) ? trim((string) $data[1]) : '';
            $qtyRaw = isset($data[2]) ? trim((string) $data[2]) : '';
            $priceRaw = isset($data[3]) ? trim((string) $data[3]) : '';

            if ($sku === '' && $nameFile === '' && $qtyRaw === '' && $priceRaw === '') {
                continue;
            }

            $row = [
                'line' => $line,
                'sku' => $sku,
                'product_name' => $nameFile,
                'qty' => $qtyRaw,
                'sale_price' => $priceRaw,
                'ok' => false,
                'error' => '',
                'product_id' => 0,
                'resolved_name' => '',
                'resolved_price' => '',
            ];

            if (count($data) < 4) {
                $row['error'] = $this->langArr['import_order_error_structure'] ?? '';
                $invalid++;
                $rows[] = $row;
                continue;
            }

            if ($this->looksLikeLegacyOrderExportRow($sku, $nameFile, $qtyRaw)) {
                $row['error'] = $this->langArr['import_order_error_wrong_format'] ?? '';
                $invalid++;
                $rows[] = $row;
                continue;
            }

            if ($sku === '') {
                $row['error'] = $this->langArr['import_order_error_sku_required'] ?? '';
                $invalid++;
                $rows[] = $row;
                continue;
            }

            $qty = $this->parsePositiveNumber($qtyRaw);
            if ($qty === null || $qty <= 0) {
                $row['error'] = $this->langArr['import_order_error_qty'] ?? '';
                $invalid++;
                $rows[] = $row;
                continue;
            }

            $price = null;
            if ($priceRaw !== '') {
                $price = $this->parsePositiveNumber($priceRaw);
                if ($price === null || $price < 0) {
                    $row['error'] = $this->langArr['import_order_error_price'] ?? '';
                    $invalid++;
                    $rows[] = $row;
                    continue;
                }
            }

            $product = $this->findProductBySku($sku, $companyId);
            if ($product === null) {
                $row['error'] = $this->langArr['import_order_error_product_not_found'] ?? '';
                $invalid++;
                $rows[] = $row;
                continue;
            }

            $productId = (int) ($product->pro_id ?? $product->product_id ?? 0);
            $resolvedName = trim((string) ($product->name ?? ''));
            $catalogPrice = (float) ($product->sale_price ?? $product->price ?? 0);
            $finalPrice = $price !== null ? $price : $catalogPrice;

            $row['ok'] = true;
            $row['qty'] = (string) $qty;
            $row['sale_price'] = (string) $finalPrice;
            $row['product_id'] = $productId;
            $row['resolved_name'] = $resolvedName !== '' ? $resolvedName : $nameFile;
            $row['resolved_price'] = (string) $finalPrice;
            $row['image'] = $this->resolveProductImageUuid($product);
            $valid++;
            $rows[] = $row;
        }

        if ($rows === []) {
            return [
                'success' => false,
                'message' => $this->langArr['import_order_error_empty'] ?? '',
                'rows' => [],
                'summary' => ['total' => 0, 'valid' => 0, 'invalid' => 0],
            ];
        }

        return [
            'success' => true,
            'can_continue' => $valid > 0 && $invalid === 0,
            'rows' => $rows,
            'summary' => [
                'total' => count($rows),
                'valid' => $valid,
                'invalid' => $invalid,
            ],
            'message' => $invalid > 0
                ? ($this->langArr['import_order_preview_has_errors'] ?? '')
                : ($this->langArr['import_order_preview_ok'] ?? ''),
        ];
    }

    private function findProductBySku(string $sku, int $companyId): ?object
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
        if ($id < 1) {
            return null;
        }

        return $product;
    }

    private function parsePositiveNumber(string $raw): ?float
    {
        $normalized = str_replace([' ', "\xc2\xa0"], '', $raw);
        $normalized = str_replace(',', '.', $normalized);
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Detecta planillas del export antiguo (nº orden, cliente, vendedor…) mal usadas como import.
     */
    private function looksLikeLegacyOrderExportRow(string $sku, string $productName, string $qtyRaw): bool
    {
        if ($this->parsePositiveNumber($qtyRaw) !== null) {
            return false;
        }

        if (preg_match('/^ORD\d+/i', $sku) === 1) {
            return true;
        }

        // Cliente suele venir como "tax / name" o similar; qty con texto (vendedor).
        if ($productName !== '' && preg_match('/\s\/\s/', $productName) === 1 && preg_match('/[A-Za-zÁÉÍÓÚáéíóúñÑ]/', $qtyRaw) === 1) {
            return true;
        }

        return false;
    }
}
