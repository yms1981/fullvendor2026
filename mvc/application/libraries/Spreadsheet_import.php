<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lectura de archivos Excel (.xlsx, .xls) para importaciones del panel company.
 */
class Spreadsheet_import
{
    /** @var bool */
    private static $autoloaded = false;

    private static function ensureAutoload(): void
    {
        if (self::$autoloaded) {
            return;
        }

        $autoload = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }

        self::$autoloaded = true;
    }

    /**
     * @return array<int, string>
     */
    public static function allowedExtensions(): array
    {
        return ['xlsx', 'xls'];
    }

    public static function isAllowedImportFilename(?string $filename): bool
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return false;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, self::allowedExtensions(), true);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function readAllRows(string $tmpPath, int $sheetIndex = 0): array
    {
        self::ensureAutoload();

        if (!is_readable($tmpPath)) {
            return [];
        }

        try {
            $spreadsheet = IOFactory::load($tmpPath);
        } catch (\Throwable $e) {
            log_message('error', 'Spreadsheet_import::readAllRows failed: ' . $e->getMessage());

            return [];
        }

        $sheet = $spreadsheet->getSheet($sheetIndex);
        $rawRows = $sheet->toArray(null, true, true, false);
        $rows = [];

        foreach ($rawRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = [];
            $hasContent = false;
            foreach ($row as $value) {
                $cell = trim((string) ($value ?? ''));
                if ($cell !== '') {
                    $hasContent = true;
                }
                $normalized[] = $cell;
            }

            if ($hasContent) {
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public static function readSheet(string $tmpPath, int $sheetIndex = 0): array
    {
        $all = self::readAllRows($tmpPath, $sheetIndex);
        if (empty($all)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_shift($all);

        return [
            'headers' => $headers,
            'rows' => $all,
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function readRows(string $tmpPath, bool $skipHeader = true, int $sheetIndex = 0): array
    {
        self::ensureAutoload();

        if (!is_readable($tmpPath)) {
            return [];
        }

        try {
            $spreadsheet = IOFactory::load($tmpPath);
        } catch (\Throwable $e) {
            log_message('error', 'Spreadsheet_import::readRows failed: ' . $e->getMessage());

            return [];
        }

        $sheet = $spreadsheet->getSheet($sheetIndex);
        $rawRows = $sheet->toArray(null, true, true, false);
        $rows = [];

        foreach ($rawRows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($skipHeader && $index === 0) {
                continue;
            }

            $normalized = [];
            $hasContent = false;
            foreach ($row as $value) {
                $cell = trim((string) ($value ?? ''));
                if ($cell !== '') {
                    $hasContent = true;
                }
                $normalized[] = $cell;
            }

            if ($hasContent) {
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string>> $rows
     */
    public static function writeTemplate(string $targetPath, array $headers, array $rows = []): bool
    {
        self::ensureAutoload();

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            if (!empty($headers)) {
                $sheet->fromArray([$headers], null, 'A1');
            }

            if (!empty($rows)) {
                $sheet->fromArray($rows, null, 'A2');
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($targetPath);

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'Spreadsheet_import::writeTemplate failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Plantillas de importación permitidas (.xlsx).
     *
     * @return array<int, string>
     */
    public static function importTemplateBasenames(): array
    {
        return [
            'categorycsv.xlsx',
            'customercsv.xlsx',
            'currenciescsv.xlsx',
            'groupscsv.xlsx',
            'new-order-example.xlsx',
            'productcsv.xlsx',
            'productimagesguide.xlsx',
            'sellerprofilescsv.xlsx',
            'termscsv.xlsx',
            'unitscsv.xlsx',
            'userscsv.xlsx',
            'warehousecsv.xlsx',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function templateSearchDirectories(): array
    {
        return [
            FCPATH . 'companyasset' . DIRECTORY_SEPARATOR . 'import-templates' . DIRECTORY_SEPARATOR,
            FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'csvdocs' . DIRECTORY_SEPARATOR,
        ];
    }

    public static function normalizeTemplateBasename(string $filename): ?string
    {
        $basename = basename(trim($filename));
        if ($basename === '') {
            return null;
        }

        $basename = preg_replace('/\.csv$/i', '.xlsx', $basename) ?? $basename;
        if (!preg_match('/\.xlsx$/i', $basename)) {
            $basename .= '.xlsx';
        }

        if (!preg_match('/^[a-z0-9_-]+\.xlsx$/i', $basename)) {
            return null;
        }

        $allowed = array_map('strtolower', self::importTemplateBasenames());
        if (!in_array(strtolower($basename), $allowed, true)) {
            return null;
        }

        return $basename;
    }

    public static function resolveImportTemplatePath(string $filename): ?string
    {
        $basename = self::normalizeTemplateBasename($filename);
        if ($basename === null) {
            return null;
        }

        foreach (self::templateSearchDirectories() as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $xlsxPath = $dir . $basename;
            $csvBasename = preg_replace('/\.xlsx$/i', '.csv', $basename) ?? $basename;
            $csvPath = $dir . $csvBasename;

            if (!is_file($xlsxPath) && is_file($csvPath)) {
                self::ensureXlsxTemplate($csvPath, $xlsxPath);
            }

            if (is_file($xlsxPath) && is_readable($xlsxPath)) {
                return $xlsxPath;
            }
        }

        return null;
    }

    /**
     * Convierte plantillas legacy CSV a XLSX si aún no existen.
     */
    public static function ensureXlsxTemplate(string $csvPath, string $xlsxPath): bool
    {
        if (is_file($xlsxPath)) {
            return true;
        }

        if (!is_readable($csvPath)) {
            return false;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return false;
        }

        $headers = fgetcsv($handle, 0, ',') ?: [];
        $rows = [];
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if ($data === null) {
                continue;
            }
            $hasContent = false;
            foreach ($data as $value) {
                if (trim((string) $value) !== '') {
                    $hasContent = true;
                    break;
                }
            }
            if ($hasContent) {
                $rows[] = array_map(static function ($value) {
                    return trim((string) $value);
                }, $data);
            }
        }
        fclose($handle);

        $dir = dirname($xlsxPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return self::writeTemplate($xlsxPath, array_map('strval', $headers), $rows);
    }

    /**
     * Definición de plantillas localizables (cabeceras y filas de ejemplo).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function importTemplateDefinitions(): array
    {
        return [
            'unitscsv.xlsx' => [
                'header_keys' => ['import_col_unit', 'import_col_description', 'import_col_format', 'import_col_decimals', 'import_col_default'],
                'example_rows' => [
                    ['import_example_unit_1', 'import_example_unit_desc_1', 'entero', '0', '1'],
                    ['import_example_unit_2', 'import_example_unit_desc_2', 'decimal', '2', '0'],
                ],
            ],
            'termscsv.xlsx' => [
                'header_keys' => ['import_col_name', 'import_col_default'],
                'example_rows' => [
                    ['import_example_term_1', '1'],
                    ['import_example_term_2', '0'],
                ],
            ],
            'groupscsv.xlsx' => [
                'header_keys' => ['import_col_name', 'import_col_percentage_type', 'import_col_percentage_amount', 'import_col_default'],
                // percentage_type uses the API enum literally so import never depends on translated aliases
                'example_rows' => [
                    ['import_example_group_1', 'Increase on Price List %', '10', '1'],
                    ['import_example_group_2', '', '0', '0'],
                ],
            ],
            'sellerprofilescsv.xlsx' => [
                'header_keys' => [
                    'import_col_profile_name', 'import_col_order_discount', 'import_col_order_disc_price',
                    'import_col_order_net_discount', 'import_col_order_net_disc_price', 'import_col_can_change_price',
                    'import_col_can_send_catalog', 'import_col_can_create_customer',
                ],
                'example_rows' => [
                    ['import_example_profile_1', '1', '15', '1', '10', '1', '1', '1'],
                    ['import_example_profile_2', '0', '0', '0', '0', '0', '1', '0'],
                ],
            ],
            'currenciescsv.xlsx' => [
                'header_keys' => ['import_col_code'],
                'example_rows' => [
                    ['USD'],
                ],
            ],
            'productcsv.xlsx' => [
                'header_keys' => [
                    'import_col_product_name', 'import_col_category_list', 'import_col_sku', 'import_col_barcode',
                    'import_col_unit_type', 'import_col_sale_price', 'import_col_fob_price', 'import_col_purchase_price',
                    'import_col_stock', 'import_col_moq', 'import_col_notify_moq', 'import_col_tags',
                    'import_col_location', 'import_col_descriptions', 'import_col_status',
                ],
                'example_rows' => [
                    ['import_example_product_name', 'import_example_category', 'SKU001', '123456789', 'import_example_unit', '41', '20', '52', '5', '56', '1', 'tag1', 'A1', 'Descripción', '1'],
                ],
            ],
            'productimagesguide.xlsx' => [
                'header_keys' => [
                    'import_col_sku', 'import_col_image_main', 'import_col_image_secondary', 'import_col_image_tertiary',
                ],
                'example_rows' => [
                    ['SKU001', 'SKU001.png', 'SKU001_pic2.png', 'SKU001_pic3.jpg'],
                ],
            ],
            'userscsv.xlsx' => [
                'header_keys' => [
                    'import_col_name',
                    'import_col_email',
                    'import_col_password',
                    'import_col_role',
                    'import_col_profile_id',
                    'import_col_phone',
                ],
                'example_rows' => [
                    ['import_example_user_1', 'manager@example.com', 'Password123', 'manager', '', ''],
                    ['import_example_user_2', 'seller@example.com', 'Password123', 'seller', '1', '5551234'],
                ],
            ],
            'warehousecsv.xlsx' => [
                'header_keys' => [
                    'import_col_name',
                    'import_col_street',
                    'import_col_street_number',
                    'import_col_country',
                    'import_col_state',
                    'import_col_city',
                ],
                'example_rows' => [
                    [
                        'import_example_warehouse_1',
                        'import_example_warehouse_street',
                        '100',
                        'Colombia',
                        'Bogota',
                        'Bogota',
                    ],
                ],
            ],
            'new-order-example.xlsx' => [
                'header_keys' => [
                    'import_order_col_sku',
                    'import_order_col_product_name',
                    'import_order_col_quantity',
                    'import_order_col_sale_price',
                ],
                'example_rows' => [
                    ['Y003-21', 'SET OF 3PCS KNIFE', '150', '15485.25'],
                    ['Y003-20', 'DISHWASHING PASTE LIME 400G WASHATTACK', '170', '20456.25'],
                    ['Y003-19', 'DISHWASHING PASTE LIME 400G WASHATTACK', '170', '20456.25'],
                ],
            ],
        ];
    }

    /**
     * Genera plantilla .xlsx temporal con textos en el idioma de la empresa.
     *
     * @param array<string, string> $langTexts
     */
    public static function buildLocalizedImportTemplate(string $basename, array $langTexts): ?string
    {
        $basename = self::normalizeTemplateBasename($basename);
        if ($basename === null) {
            return null;
        }

        $definitions = self::importTemplateDefinitions();
        $definition = $definitions[$basename] ?? null;
        if (!is_array($definition)) {
            return null;
        }

        $headers = [];
        foreach ((array) ($definition['header_keys'] ?? []) as $key) {
            $headers[] = trim((string) ($langTexts[(string) $key] ?? (string) $key));
        }

        $rows = [];
        foreach ((array) ($definition['example_rows'] ?? []) as $exampleRow) {
            $row = [];
            foreach ($exampleRow as $cellKey) {
                $cellKey = (string) $cellKey;
                if (preg_match('/^[a-z0-9_]+$/i', $cellKey) && isset($langTexts[$cellKey])) {
                    $row[] = trim((string) $langTexts[$cellKey]);
                } else {
                    $row[] = $cellKey;
                }
            }
            $rows[] = $row;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'fv_tpl_');
        if ($tmp === false) {
            return null;
        }
        $target = $tmp . '.xlsx';
        @unlink($tmp);

        if (!self::writeTemplate($target, $headers, $rows)) {
            return null;
        }

        return $target;
    }
}
