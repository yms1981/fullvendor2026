<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'core/CustomerCsvImportSupport.php';

/**
 * Esquemas de importación Excel para CRUD de configuración.
 */
class ConfigCrudImportSchemas
{
    /**
     * @return array<string, mixed>
     */
    public static function get(string $id): array
    {
        $map = [
            'units' => self::units(),
            'terms' => self::terms(),
            'groups' => self::groups(),
            'sellerprofile' => self::sellerprofile(),
            'currencies' => self::currencies(),
            'products' => self::products(),
            'warehouses' => self::warehouses(),
            'customers' => self::customers(),
            'categories' => self::categories(),
            'users' => self::users(),
            'orders' => self::orders(),
        ];

        return $map[$id] ?? [];
    }

    /**
     * Plantilla de importación de líneas de orden (SKU / nombre / cantidad / precio).
     *
     * @return array<string, mixed>
     */
    public static function orders(): array
    {
        return [
            'id' => 'orders',
            'template' => 'new-order-example.xlsx',
            'lang_section' => 'cmp-orders',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_order_col_sku', 'required' => true],
                ['index' => 1, 'label_key' => 'import_order_col_product_name', 'required' => false],
                ['index' => 2, 'label_key' => 'import_order_col_quantity', 'required' => true],
                ['index' => 3, 'label_key' => 'import_order_col_sale_price', 'required' => false],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function users(): array
    {
        return [
            'id' => 'users',
            'template' => 'userscsv.xlsx',
            'lang_section' => 'cmp-user',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_name', 'required' => true],
                ['index' => 1, 'label_key' => 'import_col_email', 'required' => true],
                ['index' => 2, 'label_key' => 'import_col_password', 'required' => true],
                [
                    'index' => 3,
                    'label_key' => 'import_col_role',
                    'help_key' => 'import_col_role_help',
                    'required' => true,
                ],
                [
                    'index' => 4,
                    'label_key' => 'import_col_profile_id',
                    'help_key' => 'import_col_profile_id_help',
                    'required' => false,
                ],
                ['index' => 5, 'label_key' => 'import_col_phone', 'required' => false],
            ],
            'duplicate_key_indexes' => [1],
            'validate' => static function (array $cells): array {
                $errors = [];
                $name = trim((string) ($cells[0] ?? ''));
                $email = trim((string) ($cells[1] ?? ''));
                $password = trim((string) ($cells[2] ?? ''));
                $role = strtolower(trim((string) ($cells[3] ?? '')));

                if ($name === '') {
                    $errors[] = 'import_col_name';
                }
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'import_col_email';
                }
                if ($password === '' || strlen($password) < 8) {
                    $errors[] = 'import_col_password';
                }
                if (!in_array($role, ['manager', 'seller', 'warehouse', 'customer'], true)) {
                    $errors[] = 'import_col_role_invalid';
                }

                return ['valid' => empty($errors), 'errors' => $errors];
            },
            'import' => static function ($controller, array $cells): bool {
                $name = trim((string) ($cells[0] ?? ''));
                $email = trim((string) ($cells[1] ?? ''));
                $password = trim((string) ($cells[2] ?? ''));
                $role = strtolower(trim((string) ($cells[3] ?? 'manager')));
                $profileId = trim((string) ($cells[4] ?? ''));
                $phone = trim((string) ($cells[5] ?? ''));

                if ($name === '' || $email === '' || $password === '') {
                    return false;
                }
                if (!in_array($role, ['manager', 'seller', 'warehouse', 'customer'], true)) {
                    return false;
                }

                $payload = [
                    'company_id' => $controller->resolveConfigImportCompanyId(),
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                ];
                if ($profileId !== '') {
                    $payload['profile_id'] = (int) $profileId;
                }
                if ($phone !== '') {
                    $payload['phone'] = $phone;
                }

                $response = $controller->post('user', $payload);

                return !$controller->hasError($response);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function units(): array
    {
        return [
            'id' => 'units',
            'template' => 'unitscsv.xlsx',
            'lang_section' => 'unit-languaje',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_unit', 'required' => true],
                ['index' => 1, 'label_key' => 'import_col_description', 'required' => true],
                ['index' => 2, 'label_key' => 'import_col_format', 'required' => false],
                ['index' => 3, 'label_key' => 'import_col_decimals', 'required' => false],
                ['index' => 4, 'label_key' => 'import_col_default', 'required' => false],
            ],
            'duplicate_key_indexes' => [0],
            'validate' => static function (array $cells): array {
                $unit = trim((string) ($cells[0] ?? ''));
                $description = trim((string) ($cells[1] ?? ''));
                $errors = [];
                if ($unit === '') {
                    $errors[] = 'import_col_unit';
                }
                if ($description === '') {
                    $errors[] = 'import_col_description';
                }
                $format = strtolower(trim((string) ($cells[2] ?? 'entero')));
                if ($format !== '' && !in_array($format, ['entero', 'decimal', 'integer', 'whole'], true)) {
                    $errors[] = 'import_col_format';
                }

                return ['valid' => empty($errors), 'errors' => $errors];
            },
            'validate_row' => static function ($controller, array $cells): array {
                $unit = trim((string) ($cells[0] ?? ''));
                $description = trim((string) ($cells[1] ?? ''));
                $errors = [];
                $errorIndexes = [];
                if ($unit === '') {
                    $errors[] = 'import_col_unit';
                    $errorIndexes[] = 0;
                }
                if ($description === '') {
                    $errors[] = 'import_col_description';
                    $errorIndexes[] = 1;
                }
                $format = strtolower(trim((string) ($cells[2] ?? 'entero')));
                if ($format !== '' && !in_array($format, ['entero', 'decimal', 'integer', 'whole'], true)) {
                    $errors[] = 'import_col_format';
                    $errorIndexes[] = 2;
                }
                if ($unit !== '' && self::unitNameExistsForImport($controller, $unit)) {
                    $errors[] = 'import_unit_exists';
                    $errorIndexes[] = 0;
                }

                return [
                    'valid' => empty($errors),
                    'errors' => $errors,
                    'error_indexes' => array_values(array_unique($errorIndexes)),
                ];
            },
            'import' => static function ($controller, array $cells): bool {
                $unit = trim((string) ($cells[0] ?? ''));
                $description = trim((string) ($cells[1] ?? ''));
                if ($unit === '' || $description === '') {
                    return false;
                }

                if (method_exists($controller, 'ensureConfigImportApiSession') && !$controller->ensureConfigImportApiSession()) {
                    if (method_exists($controller, 'setConfigImportLastError')) {
                        $lang = is_array($controller->langArr ?? null) ? $controller->langArr : [];
                        $controller->setConfigImportLastError(trim((string) ($lang['import_row_error_api_auth'] ?? 'import_row_error_api_auth')));
                    }

                    return false;
                }

                if (self::unitNameExistsForImport($controller, $unit)) {
                    if (method_exists($controller, 'setConfigImportLastError')) {
                        $lang = is_array($controller->langArr ?? null) ? $controller->langArr : [];
                        $controller->setConfigImportLastError(trim((string) ($lang['import_unit_exists'] ?? 'import_unit_exists')));
                    }

                    return false;
                }

                $formato = strtolower(trim((string) ($cells[2] ?? 'entero')));
                if (!in_array($formato, ['entero', 'decimal', 'integer', 'whole'], true)) {
                    $formato = 'entero';
                }
                if (in_array($formato, ['integer', 'whole'], true)) {
                    $formato = 'entero';
                }

                $payload = [
                    'company_id' => $controller->resolveConfigImportCompanyId(),
                    'unit' => $unit,
                    'description' => $description,
                    'formato' => $formato,
                    'decimales' => (int) trim((string) ($cells[3] ?? '0')),
                    'default' => ConfigCrudImportSchemas::parseBoolean($cells[4] ?? '0'),
                ];

                $response = $controller->post('unit', $payload);
                if ($controller->hasError($response)) {
                    $apiError = method_exists($controller, 'extractConfigImportApiErrorMessage')
                        ? $controller->extractConfigImportApiErrorMessage($response)
                        : 'error';
                    if (method_exists($controller, 'setConfigImportLastError')) {
                        $controller->setConfigImportLastError($apiError);
                    }
                    log_message('error', 'ConfigCrudImportSchemas::units import "' . $unit . '": ' . $apiError);

                    return false;
                }

                return true;
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function terms(): array
    {
        return [
            'id' => 'terms',
            'template' => 'termscsv.xlsx',
            'lang_section' => 'cmp-terms',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_name', 'required' => true],
                ['index' => 1, 'label_key' => 'import_col_default', 'required' => false],
            ],
            'duplicate_key_indexes' => [0],
            'validate' => static function (array $cells): array {
                $name = trim((string) ($cells[0] ?? ''));
                $errors = $name === '' ? ['import_col_name'] : [];

                return ['valid' => empty($errors), 'errors' => $errors];
            },
            'validate_row' => static function ($controller, array $cells): array {
                $name = trim((string) ($cells[0] ?? ''));
                if ($name === '') {
                    return ['valid' => false, 'errors' => ['import_col_name'], 'error_indexes' => [0]];
                }
                if (self::termNameExistsForImport($controller, $name)) {
                    return ['valid' => false, 'errors' => ['import_term_exists'], 'error_indexes' => [0]];
                }

                return ['valid' => true, 'errors' => []];
            },
            'import' => static function ($controller, array $cells): bool {
                $name = trim((string) ($cells[0] ?? ''));
                if ($name === '') {
                    return false;
                }
                $payload = [
                    'company_id' => $controller->resolveConfigImportCompanyId(),
                    'name' => $name,
                    'default' => self::parseBoolean($cells[1] ?? '0'),
                ];
                $response = $controller->post('termsofsale', $payload);

                return !$controller->hasError($response);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function groups(): array
    {
        return [
            'id' => 'groups',
            'template' => 'groupscsv.xlsx',
            'lang_section' => 'cmp-group',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_name', 'required' => true],
                [
                    'index' => 1,
                    'label_key' => 'import_col_percentage_type',
                    'help_key' => 'import_col_percentage_type_help',
                    'required' => false,
                ],
                ['index' => 2, 'label_key' => 'import_col_percentage_amount', 'required' => false],
                ['index' => 3, 'label_key' => 'import_col_default', 'required' => false],
            ],
            'duplicate_key_indexes' => [0],
            'validate' => static function (array $cells): array {
                $name = trim((string) ($cells[0] ?? ''));
                $errors = $name === '' ? ['import_col_name'] : [];
                $percentage = self::normalizeGroupPercentageOnPrice((string) ($cells[1] ?? ''));
                if ($percentage === false) {
                    $errors[] = 'import_col_percentage_type_invalid';
                }

                return ['valid' => empty($errors), 'errors' => $errors];
            },
            'import' => static function ($controller, array $cells): bool {
                $name = trim((string) ($cells[0] ?? ''));
                if ($name === '') {
                    return false;
                }
                $percentageOnPrice = self::normalizeGroupPercentageOnPrice((string) ($cells[1] ?? ''));
                if ($percentageOnPrice === false) {
                    return false;
                }
                $percentAmount = trim((string) ($cells[2] ?? '0'));
                if ($percentageOnPrice === null) {
                    $percentAmount = '0';
                }
                $payload = [
                    'company_id' => $controller->resolveConfigImportCompanyId(),
                    'name' => $name,
                    'percentage_on_price' => $percentageOnPrice,
                    'percent_price_amount' => $percentAmount === '' ? 0 : (float) $percentAmount,
                    'default' => self::parseBoolean($cells[3] ?? '0'),
                ];
                $response = $controller->post('customergroup', $payload);

                return !$controller->hasError($response);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function sellerprofile(): array
    {
        return [
            'id' => 'sellerprofile',
            'template' => 'sellerprofilescsv.xlsx',
            'lang_section' => 'cmp-profile-vendor',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_profile_name', 'required' => true],
                ['index' => 1, 'label_key' => 'import_col_order_discount', 'required' => false],
                ['index' => 2, 'label_key' => 'import_col_order_disc_price', 'required' => false],
                ['index' => 3, 'label_key' => 'import_col_order_net_discount', 'required' => false],
                ['index' => 4, 'label_key' => 'import_col_order_net_disc_price', 'required' => false],
                ['index' => 5, 'label_key' => 'import_col_can_change_price', 'required' => false],
                ['index' => 6, 'label_key' => 'import_col_can_send_catalog', 'required' => false],
                ['index' => 7, 'label_key' => 'import_col_can_create_customer', 'required' => false],
            ],
            'duplicate_key_indexes' => [0],
            'validate' => static function (array $cells): array {
                $name = trim((string) ($cells[0] ?? ''));
                $errors = $name === '' ? ['import_col_profile_name'] : [];

                return ['valid' => empty($errors), 'errors' => $errors];
            },
            'import' => static function ($controller, array $cells): bool {
                $profileName = trim((string) ($cells[0] ?? ''));
                if ($profileName === '') {
                    return false;
                }
                $payload = [
                    'company_id' => $controller->resolveConfigImportCompanyId(),
                    'profile_name' => $profileName,
                    'order_discount' => self::parseBoolean($cells[1] ?? '0') ? 1 : 0,
                    'order_disc_price' => trim((string) ($cells[2] ?? '0')),
                    'order_net_discount' => self::parseBoolean($cells[3] ?? '0') ? 1 : 0,
                    'order_net_disc_price' => trim((string) ($cells[4] ?? '0')),
                    'can_change_price' => self::parseBoolean($cells[5] ?? '0') ? 1 : 0,
                    'can_send_catalog' => self::parseBoolean($cells[6] ?? '0') ? 1 : 0,
                    'can_create_customer' => self::parseBoolean($cells[7] ?? '0') ? 1 : 0,
                    'status' => 1,
                ];
                $response = $controller->post('sellerprofiles', $payload);

                return !$controller->hasError($response);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function currencies(): array
    {
        return [
            'id' => 'currencies',
            'template' => 'currenciescsv.xlsx',
            'lang_section' => 'cm-currency',
            'single_row' => true,
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_code', 'required' => true],
            ],
            'validate_row' => static function ($controller, array $cells): array {
                $code = strtoupper(trim((string) ($cells[0] ?? '')));
                if (strlen($code) < 3) {
                    return ['valid' => false, 'errors' => ['import_col_code']];
                }
                if (self::resolveCurrencyIdByCode($controller, $code) === null) {
                    return ['valid' => false, 'errors' => ['import_currency_not_found']];
                }

                return ['valid' => true, 'errors' => []];
            },
            'duplicate_key_indexes' => [0],
            'validate' => static function (array $cells): array {
                $code = strtoupper(trim((string) ($cells[0] ?? '')));
                $errors = strlen($code) < 3 ? ['import_col_code'] : [];

                return ['valid' => empty($errors), 'errors' => $errors];
            },
            'import' => static function ($controller, array $cells): bool {
                $code = strtoupper(trim((string) ($cells[0] ?? '')));
                if ($code === '') {
                    return false;
                }
                $currencyId = self::resolveCurrencyIdByCode($controller, $code);
                if ($currencyId === null) {
                    return false;
                }
                $companyId = (int) $controller->resolveConfigImportCompanyId();
                $response = $controller->put("company/{$companyId}", ['currency' => $currencyId]);

                return !$controller->hasError($response);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function products(): array
    {
        return [
            'id' => 'products',
            'template' => 'productcsv.xlsx',
            'lang_section' => 'cmp-products',
            'import_title_keys' => ['import_page_title', 'bnImport'],
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_product_name', 'help_key' => 'import_col_product_name_help', 'required' => true],
                ['index' => 1, 'label_key' => 'import_col_category_list', 'help_key' => 'import_col_category_list_help', 'required' => true],
                ['index' => 2, 'label_key' => 'import_col_sku', 'help_key' => 'import_col_sku_help', 'required' => true],
                ['index' => 3, 'label_key' => 'import_col_barcode', 'help_key' => 'import_col_barcode_help', 'required' => false],
                ['index' => 4, 'label_key' => 'import_col_unit_type', 'help_key' => 'import_col_unit_type_help', 'required' => false],
                ['index' => 5, 'label_key' => 'import_col_sale_price', 'help_key' => 'import_col_sale_price_help', 'required' => true],
                ['index' => 6, 'label_key' => 'import_col_fob_price', 'help_key' => 'import_col_fob_price_help', 'required' => true],
                ['index' => 7, 'label_key' => 'import_col_purchase_price', 'help_key' => 'import_col_purchase_price_help', 'required' => true],
                ['index' => 8, 'label_key' => 'import_col_stock', 'help_key' => 'import_col_stock_help', 'required' => false],
                ['index' => 9, 'label_key' => 'import_col_moq', 'help_key' => 'import_col_moq_help', 'required' => false],
                ['index' => 10, 'label_key' => 'import_col_notify_moq', 'help_key' => 'import_col_notify_moq_help', 'required' => false],
                ['index' => 11, 'label_key' => 'import_col_tags', 'help_key' => 'import_col_tags_help', 'required' => false],
                ['index' => 12, 'label_key' => 'import_col_location', 'help_key' => 'import_col_location_help', 'required' => false],
                ['index' => 13, 'label_key' => 'import_col_descriptions', 'help_key' => 'import_col_descriptions_help', 'required' => false],
                ['index' => 14, 'label_key' => 'import_col_status', 'help_key' => 'import_col_status_help', 'required' => true],
            ],
            'duplicate_key_indexes' => [2],
            'validate_row' => static function ($controller, array $cells): array {
                if (!method_exists($controller, 'validateProductCsvRow')) {
                    return ['valid' => false, 'errors' => ['message_error_generic']];
                }

                return $controller->validateProductCsvRow($cells);
            },
            'import' => static function ($controller, array $cells): bool {
                if (!method_exists($controller, 'importProductCsvRow')) {
                    return false;
                }

                return $controller->importProductCsvRow($cells);
            },
            'import_batch_size' => 25,
            'after_confirm' => static function ($controller, int $loaded, int $failed): void {
                if ($loaded < 1 || !method_exists($controller, 'getCompanyId')) {
                    return;
                }
                $controller->post('importhistory', [
                    'company_id' => $controller->resolveConfigImportCompanyId(),
                    'import_date' => date('Y-m-d H:i:s'),
                    'importedfile' => 'product',
                    'records_imported' => $loaded,
                ]);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function customers(): array
    {
        return [
            'id' => 'customers',
            'template' => 'customercsv.xlsx',
            'lang_section' => 'cmp-clientes',
            'import_title_keys' => ['importar_clientes', 'import_page_title'],
            'columns' => [
                ['index' => 0, 'label_key' => 'field_cust_id_kor', 'help_key' => 'import_col_erp_id_help', 'required' => true],
                ['index' => 1, 'label_key' => 'col_nombre', 'help_key' => 'import_col_name_help', 'required' => true],
                ['index' => 2, 'label_key' => 'col_business_name', 'help_key' => 'import_col_business_name_help', 'required' => true],
                ['index' => 3, 'label_key' => 'col_tax', 'required' => false],
                ['index' => 4, 'label_key' => 'col_discount', 'required' => false],
                ['index' => 5, 'label_key' => 'col_term', 'help_key' => 'import_col_term_help', 'required' => false],
                ['index' => 6, 'label_key' => 'col_group', 'help_key' => 'import_col_group_help', 'required' => false],
                ['index' => 7, 'label_key' => 'col_email', 'help_key' => 'import_col_email_help', 'required' => false],
                ['index' => 8, 'label_key' => 'col_phone', 'required' => false],
                ['index' => 9, 'label_key' => 'col_cellphone', 'required' => false],
                ['index' => 10, 'label_key' => 'col_seller', 'help_key' => 'import_col_seller_help', 'required' => false],
                ['index' => 11, 'label_key' => 'col_notes', 'required' => false],
                ['index' => 12, 'label_key' => 'col_commercial_address', 'required' => false],
                ['index' => 13, 'label_key' => 'col_commercial_delivery_address', 'required' => false],
                ['index' => 14, 'label_key' => 'col_commercial_country', 'required' => false],
                ['index' => 15, 'label_key' => 'col_state_commercial', 'required' => false],
                ['index' => 16, 'label_key' => 'col_commercial_city', 'required' => false],
                ['index' => 17, 'label_key' => 'col_commercial_zone', 'required' => false],
                ['index' => 18, 'label_key' => 'col_commercial_zip_code', 'required' => false],
                ['index' => 19, 'label_key' => 'col_dispatch_address', 'required' => false],
                ['index' => 20, 'label_key' => 'col_dispatch_delivery_address', 'required' => false],
                ['index' => 21, 'label_key' => 'col_dispatch_country', 'required' => false],
                ['index' => 22, 'label_key' => 'col_dispatch_state', 'required' => false],
                ['index' => 23, 'label_key' => 'col_dispatch_city', 'required' => false],
                ['index' => 24, 'label_key' => 'col_dispatch_zone', 'required' => false],
                ['index' => 25, 'label_key' => 'col_dispatch_zip_code', 'required' => false],
                ['index' => 26, 'label_key' => 'col_dispatch_shipping_notes', 'required' => false],
                ['index' => 27, 'label_key' => 'col_catalog_emails', 'help_key' => 'import_col_catalog_emails_help', 'required' => false],
                ['index' => 28, 'label_key' => 'col_customer_status', 'help_key' => 'import_col_status_help', 'required' => false],
            ],
            'duplicate_key_indexes' => [2],
            'validate_row' => static function ($controller, array $cells): array {
                return CustomerCsvImportSupport::validateRow($controller, $cells);
            },
            'import' => static function ($controller, array $cells): bool {
                return CustomerCsvImportSupport::importRow($controller, $cells);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function warehouses(): array
    {
        return [
            'id' => 'warehouses',
            'template' => 'warehousecsv.xlsx',
            'lang_section' => 'cmp-warehouse',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_name', 'help_key' => 'import_col_name_help', 'required' => true],
                ['index' => 1, 'label_key' => 'import_col_street', 'help_key' => 'import_col_street_help', 'required' => true],
                ['index' => 2, 'label_key' => 'import_col_street_number', 'help_key' => 'import_col_street_number_help', 'required' => true],
                ['index' => 3, 'label_key' => 'import_col_country', 'help_key' => 'import_col_country_help', 'required' => true],
                ['index' => 4, 'label_key' => 'import_col_state', 'help_key' => 'import_col_state_help', 'required' => true],
                ['index' => 5, 'label_key' => 'import_col_city', 'help_key' => 'import_col_city_help', 'required' => true],
            ],
            'duplicate_key_indexes' => [0],
            'validate_row' => static function ($controller, array $cells): array {
                return self::validateWarehouseImportRow($controller, $cells);
            },
            'import' => static function ($controller, array $cells): bool {
                return self::importWarehouseRow($controller, $cells);
            },
        ];
    }

    /**
     * @param object $controller
     * @param array<int, string> $cells
     * @return array{valid: bool, errors: array<int, string>, error_indexes?: array<int, int>}
     */
    private static function validateWarehouseImportRow($controller, array $cells): array
    {
        $name = trim((string) ($cells[0] ?? ''));
        $street = trim((string) ($cells[1] ?? ''));
        $streetNumber = trim((string) ($cells[2] ?? ''));
        $countryValue = trim((string) ($cells[3] ?? ''));
        $stateValue = trim((string) ($cells[4] ?? ''));
        $cityValue = trim((string) ($cells[5] ?? ''));

        $errors = [];
        $errorIndexes = [];

        if ($name === '') {
            $errors[] = 'import_col_name';
        }
        if ($street === '') {
            $errors[] = 'import_col_street';
        }
        if ($streetNumber === '') {
            $errors[] = 'import_col_street_number';
        }
        if ($countryValue === '') {
            $errors[] = 'import_col_country';
        }
        if ($stateValue === '') {
            $errors[] = 'import_col_state';
        }
        if ($cityValue === '') {
            $errors[] = 'import_col_city';
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors];
        }

        [$countriesMap, $statesMap, $citiesMap] = self::getWarehouseLocationMaps($controller);

        if (self::resolveWarehouseLocationId($countryValue, $countriesMap) === null) {
            return ['valid' => false, 'errors' => ['import_country_not_found'], 'error_indexes' => [3]];
        }
        if (self::resolveWarehouseLocationId($stateValue, $statesMap) === null) {
            return ['valid' => false, 'errors' => ['import_state_not_found'], 'error_indexes' => [4]];
        }
        if (self::resolveWarehouseLocationId($cityValue, $citiesMap) === null) {
            return ['valid' => false, 'errors' => ['import_city_not_found'], 'error_indexes' => [5]];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * @param object $controller
     * @param array<int, string> $cells
     */
    private static function importWarehouseRow($controller, array $cells): bool
    {
        $name = trim((string) ($cells[0] ?? ''));
        $street = trim((string) ($cells[1] ?? ''));
        $streetNumber = trim((string) ($cells[2] ?? ''));
        $countryValue = trim((string) ($cells[3] ?? ''));
        $stateValue = trim((string) ($cells[4] ?? ''));
        $cityValue = trim((string) ($cells[5] ?? ''));

        if ($name === '' || $street === '' || $streetNumber === ''
            || $countryValue === '' || $stateValue === '' || $cityValue === '') {
            return false;
        }

        [$countriesMap, $statesMap, $citiesMap] = self::getWarehouseLocationMaps($controller);
        $countryId = self::resolveWarehouseLocationId($countryValue, $countriesMap);
        $stateId = self::resolveWarehouseLocationId($stateValue, $statesMap);
        $cityId = self::resolveWarehouseLocationId($cityValue, $citiesMap);

        if ($countryId === null || $stateId === null || $cityId === null) {
            return false;
        }

        $payload = [
            'company_id' => $controller->resolveConfigImportCompanyId(),
            'name' => $name,
            'street' => $street,
            'street_number' => $streetNumber,
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
        ];

        $response = $controller->post('warehouse', $payload);

        return !$controller->hasError($response);
    }

    /**
     * @param object $controller
     * @return array{0: array<string, int>, 1: array<string, int>, 2: array<string, int>}
     */
    private static function getWarehouseLocationMaps($controller): array
    {
        static $cache = [];
        $key = spl_object_hash($controller);
        if (!isset($cache[$key])) {
            $cache[$key] = self::buildWarehouseLocationMaps($controller);
        }

        return $cache[$key];
    }

    /**
     * @param object $controller
     * @return array{0: array<string, int>, 1: array<string, int>, 2: array<string, int>}
     */
    private static function buildWarehouseLocationMaps($controller): array
    {
        $countries = self::fetchWarehouseImportList($controller, 'country', ['props' => ['id', 'name'], 'all' => true]);
        $states = self::fetchWarehouseImportList($controller, 'state', ['props' => ['id', 'name', 'country_id'], 'all' => true]);
        $cities = self::fetchWarehouseImportList($controller, 'city', ['props' => ['id', 'name', 'state_id'], 'all' => true]);

        return [
            self::mapWarehouseImportByName($countries),
            self::mapWarehouseImportByName($states),
            self::mapWarehouseImportByName($cities),
        ];
    }

    /**
     * @param object $controller
     * @return array<int, object|array>
     */
    private static function fetchWarehouseImportList($controller, string $resource, array $params): array
    {
        $response = $controller->getObjectFromApi($resource, $params);
        if (is_object($response) && isset($response->data)) {
            return is_array($response->data) ? $response->data : [];
        }
        if (is_array($response) && !isset($response['error'])) {
            return $response;
        }

        return [];
    }

    /**
     * @param array<int, object|array> $items
     * @return array<string, int>
     */
    private static function mapWarehouseImportByName(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $item = (object) $item;
            $name = isset($item->name) ? strtolower(trim((string) $item->name)) : '';
            $id = isset($item->id) ? (int) $item->id : 0;
            if ($name !== '' && $id > 0) {
                $map[$name] = $id;
            }
        }

        return $map;
    }

    /**
     * @param array<string, int> $map
     */
    private static function resolveWarehouseLocationId(string $value, array $map): ?int
    {
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return $map[strtolower($value)] ?? null;
    }

    public static function parseBoolean($value): bool
    {
        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['1', 'true', 'yes', 'si', 'sí', 'on'], true);
    }

    /**
     * Normaliza el tipo de porcentaje al valor de ENUM de la API, o null (= ninguno).
     * Devuelve false si el texto no es reconocido.
     *
     * @return string|null|false
     */
    public static function normalizeGroupPercentageOnPrice(string $value)
    {
        $trimmed = trim($value);
        $normalized = strtolower($trimmed);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if ($normalized === '' || $normalized === '0' || in_array($normalized, [
            'nothing', 'null', 'none', 'ninguno', 'ninguna', 'n/a', '-',
        ], true)) {
            return null;
        }

        // Template / translation key accidentally left unresolved in a downloaded file
        if ($normalized === 'import_example_group_pct_type') {
            return 'Increase on Price List %';
        }

        $increaseAliases = [
            'increase on price list %',
            'increase on price list',
            'incremento sobre lista de precios %',
            'incremento sobre lista de precios',
            'aumento sobre lista %',
            'aumento sobre lista',
            'aumento sobre a lista de preços %',
            'aumento sul listino prezzi %',
        ];
        $decreaseAliases = [
            'decrease on price list %',
            'decrease on price list',
            'decremento sobre lista de precios %',
            'decremento sobre lista de precios',
            'disminucion sobre lista de precios %',
            'disminución sobre lista de precios %',
            'diminuição sobre a lista de preços %',
            'diminuzione sul listino prezzi %',
        ];

        if (in_array($normalized, $increaseAliases, true) || str_contains($normalized, 'increase')) {
            return 'Increase on Price List %';
        }
        if (in_array($normalized, $decreaseAliases, true) || str_contains($normalized, 'decrease')) {
            return 'Decrease on Price List %';
        }
        if (str_contains($normalized, 'increment') || str_contains($normalized, 'aumento')) {
            return 'Increase on Price List %';
        }
        if (
            str_contains($normalized, 'decrement')
            || str_contains($normalized, 'disminuc')
            || str_contains($normalized, 'diminui')
        ) {
            return 'Decrease on Price List %';
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function categories(): array
    {
        return [
            'id' => 'categories',
            'template' => 'categorycsv.xlsx',
            'lang_section' => 'cmp-categorias',
            'columns' => [
                ['index' => 0, 'label_key' => 'import_col_name', 'help_key' => 'import_col_name_help', 'required' => true],
            ],
            'duplicate_key_indexes' => [0],
            'validate' => static function (array $cells): array {
                $name = trim((string) ($cells[0] ?? ''));
                $errors = $name === '' ? ['import_col_name'] : [];

                return ['valid' => empty($errors), 'errors' => $errors];
            },
            'validate_row' => static function ($controller, array $cells): array {
                $name = trim((string) ($cells[0] ?? ''));
                if ($name === '') {
                    return ['valid' => false, 'errors' => ['import_col_name'], 'error_indexes' => [0]];
                }
                if (self::categoryNameExistsForImport($controller, $name)) {
                    return ['valid' => false, 'errors' => ['import_category_exists'], 'error_indexes' => [0]];
                }

                return ['valid' => true, 'errors' => []];
            },
            'import' => static function ($controller, array $cells): bool {
                $name = trim((string) ($cells[0] ?? ''));
                if ($name === '') {
                    return false;
                }

                $payload = [
                    'company_id' => $controller->resolveConfigImportCompanyId(),
                    'category_name' => $name,
                ];
                $response = $controller->post('category', $payload);

                return !$controller->hasError($response);
            },
        ];
    }

    private static function termNameExistsForImport($controller, string $name): bool
    {
        $companyId = (int) $controller->resolveConfigImportCompanyId();
        if ($companyId <= 0) {
            return false;
        }

        static $cache = [];
        if (!isset($cache[$companyId])) {
            $names = [];
            $response = $controller->getDataFromApi('termsofsale/all', [
                'company' => $companyId,
                'props' => ['name'],
            ]);
            if (!$controller->hasError($response)) {
                $rows = [];
                if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
                    $rows = $response['data'];
                } elseif (is_array($response)) {
                    $rows = $response;
                } elseif (is_object($response) && isset($response->data) && is_array($response->data)) {
                    $rows = $response->data;
                }
                foreach ($rows as $row) {
                    $row = (array) $row;
                    $termName = trim((string) ($row['name'] ?? ''));
                    if ($termName !== '') {
                        $names[mb_strtolower($termName)] = true;
                    }
                }
            }
            $cache[$companyId] = $names;
        }

        return !empty($cache[$companyId][mb_strtolower($name)]);
    }

    /**
     * @param object $controller
     */
    private static function unitNameExistsForImport($controller, string $name): bool
    {
        $companyId = (int) $controller->resolveConfigImportCompanyId();
        if ($companyId <= 0 || trim($name) === '') {
            return false;
        }

        static $cache = [];
        if (!isset($cache[$companyId])) {
            $names = [];
            $response = $controller->getObjectFromApi("company/{$companyId}/units");
            if (!$controller->hasError($response)) {
                $rows = [];
                if (is_object($response) && isset($response->data) && is_array($response->data)) {
                    $rows = $response->data;
                } elseif (is_array($response) && isset($response['data']) && is_array($response['data'])) {
                    $rows = $response['data'];
                } elseif (is_array($response)) {
                    $rows = $response;
                }
                foreach ($rows as $row) {
                    $row = (array) $row;
                    $unitName = trim((string) ($row['unit'] ?? ''));
                    if ($unitName !== '') {
                        $names[mb_strtolower($unitName)] = true;
                    }
                }
            }
            $cache[$companyId] = $names;
        }

        return !empty($cache[$companyId][mb_strtolower(trim($name))]);
    }

    /**
     * @param object $controller
     */
    private static function categoryNameExistsForImport($controller, string $name): bool
    {
        $companyId = (int) $controller->resolveConfigImportCompanyId();
        if ($companyId <= 0) {
            return false;
        }

        static $cache = [];
        if (!isset($cache[$companyId])) {
            $names = [];
            $response = $controller->getDataFromApi('category', [
                'company' => $companyId,
                'all' => true,
                'props' => ['category_name'],
            ]);
            if (!$controller->hasError($response)) {
                $rows = [];
                if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
                    $rows = $response['data'];
                } elseif (is_array($response)) {
                    $rows = $response;
                } elseif (is_object($response) && isset($response->data) && is_array($response->data)) {
                    $rows = $response->data;
                }
                foreach ($rows as $row) {
                    $row = (array) $row;
                    $categoryName = trim((string) ($row['category_name'] ?? ''));
                    if ($categoryName !== '') {
                        $names[mb_strtolower($categoryName)] = true;
                    }
                }
            }
            $cache[$companyId] = $names;
        }

        return !empty($cache[$companyId][mb_strtolower($name)]);
    }

    /**
     * @param object $controller
     */
    private static function resolveCurrencyIdByCode($controller, string $code): ?int
    {
        $response = $controller->getObjectFromApi('currency/grouped-by-continent');
        $groups = [];
        if (is_object($response) && isset($response->data) && is_array($response->data)) {
            $groups = $response->data;
        } elseif (is_array($response) && !isset($response['error'])) {
            $groups = $response;
        }

        foreach ($groups as $group) {
            $group = (object) $group;
            $children = is_array($group->currencies ?? null) ? $group->currencies : [];
            foreach ($children as $child) {
                $child = (object) $child;
                $childCode = strtoupper(trim((string) ($child->code ?? $child->Code ?? '')));
                if ($childCode === $code) {
                    $id = $child->id ?? $child->currency_id ?? null;

                    return $id !== null ? (int) $id : null;
                }
            }
        }

        return null;
    }
}
