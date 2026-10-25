<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Validación e importación de clientes desde Excel (plantilla customercsv.xlsx).
 */
class CustomerCsvImportSupport
{
    /** @var array<int, array<string, mixed>> */
    private static $cacheByCompany = [];

    /**
     * Normaliza una fila al orden de la plantilla (incluye remapeo de export legacy).
     *
     * @param array<int, string> $cells
     * @return array<int, string>
     */
    public static function normalizeImportRow(array $cells, bool $forceLegacy = false): array
    {
        return self::normalizeCells($cells, $forceLegacy);
    }

    /**
     * Detecta cabeceras del Excel de exportación antiguo
     * (Name, Business Name, ID, ID ERP, …).
     *
     * @param array<int, mixed> $headers
     */
    public static function isLegacyExportHeaders(array $headers): bool
    {
        $norm = [];
        foreach (array_slice(array_values($headers), 0, 4) as $header) {
            $cleaned = preg_replace('/[^a-z0-9]+/i', '', (string) $header);
            $norm[] = strtolower((string) $cleaned);
        }

        $h0 = $norm[0] ?? '';
        $h1 = $norm[1] ?? '';
        $h2 = $norm[2] ?? '';
        $h3 = $norm[3] ?? '';

        if ($h0 === 'name' && strpos($h1, 'business') !== false && $h2 === 'id') {
            return true;
        }

        if (($h0 === 'nombre' || $h0 === 'name') && $h2 === 'id' && (strpos($h3, 'erp') !== false || $h3 === 'iderp')) {
            return true;
        }

        return false;
    }

    /**
     * @param object $controller Company_Controller
     * @param array<int, string> $cells
     * @return array{valid: bool, skip?: bool, errors: array<int, string>, error_indexes?: array<int, int>, cells?: array<int, string>}
     */
    public static function validateRow($controller, array $cells): array
    {
        $cells = self::normalizeCells($cells);
        $errors = [];
        $errorIndexes = [];

        self::syncNameAndBusinessName($cells);
        $cells[7] = self::extractFirstEmail((string) ($cells[7] ?? ''));

        $name = trim((string) ($cells[1] ?? ''));
        $businessName = trim((string) ($cells[2] ?? ''));

        // Sin nombre ni empresa: omitir la fila (no es error de importación).
        if ($name === '' && $businessName === '') {
            return [
                'valid' => false,
                'skip' => true,
                'errors' => ['import_row_skipped_no_name'],
                'error_indexes' => [1, 2],
                'cells' => $cells,
            ];
        }

        $erpId = trim((string) ($cells[0] ?? ''));
        if ($erpId === '') {
            $errors[] = 'error_erp_id';
            $errorIndexes[] = 0;
        }

        $companyId = self::resolveCompanyId($controller);
        if ($companyId > 0) {
            $cache = &self::getCache($controller, $companyId);
            $termFallback = self::resolveDefaultId($cache, 'term');
            $groupFallback = self::resolveDefaultId($cache, 'group');

            if (trim((string) ($cells[5] ?? '')) === '' && $termFallback === null) {
                $errors[] = 'import_row_error_term_default';
                $errorIndexes[] = 5;
            }
            if (trim((string) ($cells[6] ?? '')) === '' && $groupFallback === null) {
                $errors[] = 'import_row_error_group_default';
                $errorIndexes[] = 6;
            }
        }

        $statusRaw = trim((string) ($cells[28] ?? ''));
        if ($statusRaw !== '') {
            $normalizedStatus = self::normalizeCustomerStatus($statusRaw);
            if ($normalizedStatus === null) {
                $errors[] = 'import_col_status_invalid';
                $errorIndexes[] = 28;
            } else {
                $cells[28] = $normalizedStatus;
            }
        }

        return [
            'valid' => empty($errors),
            'skip' => false,
            'errors' => $errors,
            'error_indexes' => $errorIndexes,
            'cells' => $cells,
        ];
    }

    /**
     * Si falta nombre o empresa, copia el otro (pueden ser iguales).
     *
     * @param array<int, string> $cells
     */
    private static function syncNameAndBusinessName(array &$cells): void
    {
        $name = trim((string) ($cells[1] ?? ''));
        $businessName = trim((string) ($cells[2] ?? ''));

        if ($name === '' && $businessName !== '') {
            $cells[1] = $businessName;
        } elseif ($businessName === '' && $name !== '') {
            $cells[2] = $name;
        } else {
            $cells[1] = $name;
            $cells[2] = $businessName;
        }
    }

    /**
     * Si hay varios correos, usa el primero válido. Si el formato no es válido, lo elimina.
     */
    private static function extractFirstEmail(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $raw, $matches) && !empty($matches[0])) {
            foreach ($matches[0] as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    return $candidate;
                }
            }

            return '';
        }

        $parts = preg_split('/[;,\|\s\/]+/', $raw) ?: [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '' && filter_var($part, FILTER_VALIDATE_EMAIL)) {
                return $part;
            }
        }

        return '';
    }

    /**
     * @param object $controller
     */
    private static function resolveCompanyId($controller): int
    {
        $companyId = 0;
        if (method_exists($controller, 'resolveConfigImportCompanyId')) {
            $companyId = (int) $controller->resolveConfigImportCompanyId();
        } elseif (method_exists($controller, 'getCompanyId')) {
            try {
                $companyId = (int) $controller->getCompanyId();
            } catch (\Throwable $e) {
                $companyId = 0;
            }
        }

        return $companyId;
    }

    /**
     * @param array<string, mixed> $cache
     */
    private static function resolveDefaultId(array $cache, string $type): ?int
    {
        if ($type === 'term') {
            $id = $cache['defaultTermId'] ?? null;
            if ($id === null || $id === '' || (int) $id <= 0) {
                $id = $cache['firstTermId'] ?? null;
            }
        } else {
            $id = $cache['defaultGroupId'] ?? null;
            if ($id === null || $id === '' || (int) $id <= 0) {
                $id = $cache['firstGroupId'] ?? null;
            }
        }

        $id = $id !== null && $id !== '' ? (int) $id : null;

        return ($id !== null && $id > 0) ? $id : null;
    }

    /**
     * Normaliza estados textuales frecuentes a 0/1.
     */
    private static function normalizeCustomerStatus(string $value): ?string
    {
        $v = strtolower(trim($value));
        if ($v === '0' || $v === '1') {
            return $v;
        }
        // Excel a veces serializa 1 como "1.0"
        if (is_numeric($value) && ((float) $value === 0.0 || (float) $value === 1.0)) {
            return ((float) $value === 1.0) ? '1' : '0';
        }
        if (in_array($v, ['active', 'activo', 'activa', 'yes', 'si', 'sí', 'true', 'on'], true)) {
            return '1';
        }
        if (in_array($v, ['inactive', 'inactivo', 'inactiva', 'no', 'false', 'off', 'disabled'], true)) {
            return '0';
        }

        return null;
    }

    private static function resolveCustomerStatusForPayload($value): int
    {
        $normalized = self::normalizeCustomerStatus(trim((string) $value));
        if ($normalized === null || $normalized === '') {
            return 1;
        }

        return (int) $normalized;
    }

    /**
     * @param object $controller Company_Controller
     * @param array<int, string> $cells
     */
    public static function importRow($controller, array $cells): bool
    {
        $cells = self::normalizeCells($cells);
        self::syncNameAndBusinessName($cells);
        $cells[7] = self::extractFirstEmail((string) ($cells[7] ?? ''));

        $name = trim((string) ($cells[1] ?? ''));
        $businessName = trim((string) ($cells[2] ?? ''));
        if ($name === '' && $businessName === '') {
            // Fila omitida: sin nombre ni empresa.
            return true;
        }

        $companyId = self::resolveCompanyId($controller);
        if ($companyId <= 0) {
            if (method_exists($controller, 'setConfigImportLastError')) {
                $controller->setConfigImportLastError('import_row_error_company');
            }

            return false;
        }

        $cache = &self::getCache($controller, $companyId);
        $customerData = self::buildCustomerPayload($cells, $companyId, $cache);

        $erpId = trim((string) ($customerData['cust_id_kor'] ?? ''));
        $name = trim((string) ($customerData['name'] ?? ''));
        $businessName = trim((string) ($customerData['business_name'] ?? ''));
        $email = trim((string) ($customerData['email'] ?? ''));

        if ($erpId === '') {
            if (method_exists($controller, 'setConfigImportLastError')) {
                $controller->setConfigImportLastError('error_erp_id');
            }

            return false;
        }
        if ($name === '' || $businessName === '') {
            // Ya sincronizados; si aún faltan ambos se omitió arriba.
            if (method_exists($controller, 'setConfigImportLastError')) {
                $controller->setConfigImportLastError('import_row_skipped_no_name');
            }

            return false;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $customerData['email'] = null;
        }
        if (empty($customerData['term_id'])) {
            if (method_exists($controller, 'setConfigImportLastError')) {
                $controller->setConfigImportLastError('import_row_error_term_default');
            }

            return false;
        }
        if (empty($customerData['group_id'])) {
            if (method_exists($controller, 'setConfigImportLastError')) {
                $controller->setConfigImportLastError('import_row_error_group_default');
            }

            return false;
        }

        $existing = self::resolveExistingCustomerForImport($controller, $companyId, $businessName, $erpId);
        $customerId = $existing['customer_id'];
        $hasPortalUser = $existing['has_portal_user'];
        $existingEmail = $existing['email'];

        if ($customerId) {
            $response = $controller->put('customer/' . $customerId, $customerData);
        } else {
            $response = $controller->post('customer', $customerData);
            // Si ya existía (p. ej. búsqueda falló) y choca unique: recuperar y actualizar.
            if ($controller->hasError($response) && self::isUniqueBusinessNameImportError(self::extractImportApiErrorMessage($response))) {
                $existing = self::resolveExistingCustomerForImport($controller, $companyId, $businessName, $erpId);
                $customerId = $existing['customer_id'];
                $hasPortalUser = $existing['has_portal_user'];
                $existingEmail = $existing['email'];
                if ($customerId) {
                    $response = $controller->put('customer/' . $customerId, $customerData);
                }
            }
        }

        if ($controller->hasError($response)) {
            if (method_exists($controller, 'setConfigImportLastError')) {
                $message = self::extractImportApiErrorMessage($response);
                $controller->setConfigImportLastError($message !== '' ? $message : 'import_row_error_generic');
            }

            return false;
        }

        if (!$customerId) {
            $customerId = self::extractCustomerIdFromResponse($response);
            $hasPortalUser = false;
        }
        if (!$customerId) {
            $existing = self::resolveExistingCustomerForImport($controller, $companyId, $businessName, $erpId);
            $customerId = $existing['customer_id'];
            $hasPortalUser = $existing['has_portal_user'];
            if ($existingEmail === '') {
                $existingEmail = $existing['email'];
            }
        }

        // Email del Excel o, si falta, el que ya tenía el cliente en BD.
        $portalEmail = trim((string) ($customerData['email'] ?? ''));
        if ($portalEmail === '' || !filter_var($portalEmail, FILTER_VALIDATE_EMAIL)) {
            $portalEmail = $existingEmail;
        }

        // Solo crear usuario si el cliente no tiene ninguno vinculado y hay email válido.
        if (
            $customerId
            && !$hasPortalUser
            && $portalEmail !== ''
            && filter_var($portalEmail, FILTER_VALIDATE_EMAIL)
        ) {
            $userName = $name !== '' ? $name : $businessName;
            if (!self::ensureCustomerPortalUser($controller, $companyId, $customerId, $userName, $portalEmail)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Localiza cliente existente por business_name o Id ERP.
     *
     * @param object $controller
     * @return array{customer_id: ?int, has_portal_user: bool, email: string}
     */
    private static function resolveExistingCustomerForImport(
        $controller,
        int $companyId,
        string $businessName,
        string $erpId
    ): array {
        $empty = [
            'customer_id' => null,
            'has_portal_user' => false,
            'email' => '',
        ];

        if ($businessName !== '') {
            $found = self::parseExistingCustomerLookup(
                $controller->getDataFromApi(
                    'customer/company/' . $companyId . '/find-by-business-name',
                    ['business_name' => $businessName]
                ),
                $controller
            );
            if ($found['customer_id']) {
                return $found;
            }
        }

        if ($erpId !== '') {
            $found = self::parseExistingCustomerLookup(
                $controller->getDataFromApi(
                    'customer/company/' . $companyId . '/find-by-erp',
                    ['cust_id_kor' => $erpId]
                ),
                $controller
            );
            if ($found['customer_id']) {
                return $found;
            }
        }

        return $empty;
    }

    /**
     * @param mixed $response
     * @param object $controller
     * @return array{customer_id: ?int, has_portal_user: bool, email: string}
     */
    private static function parseExistingCustomerLookup($response, $controller): array
    {
        $result = [
            'customer_id' => null,
            'has_portal_user' => false,
            'email' => '',
        ];

        if ($controller->hasError($response)) {
            return $result;
        }

        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        if (!is_array($response)) {
            return $result;
        }

        $data = $response['data'] ?? $response;
        if (!is_array($data)) {
            return $result;
        }

        $customerId = isset($data['customer_id']) ? (int) $data['customer_id'] : 0;
        if ($customerId <= 0) {
            return $result;
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        $hasPortalUser = !empty($data['has_portal_user']);
        if (!$hasPortalUser && isset($data['users_count'])) {
            $hasPortalUser = (int) $data['users_count'] > 0;
        }

        return [
            'customer_id' => $customerId,
            'has_portal_user' => $hasPortalUser,
            'email' => $email,
        ];
    }

    private static function isUniqueBusinessNameImportError(string $message): bool
    {
        $m = mb_strtolower(trim($message));
        if ($m === '') {
            return false;
        }

        return str_contains($m, 'business_name')
            || str_contains($m, 'nombre de empresa')
            || str_contains($m, 'business name')
            || str_contains($m, 'has already been taken')
            || str_contains($m, 'ya ha sido tomado')
            || str_contains($m, 'ya está en uso');
    }

    /**
     * Crea (o reutiliza) el usuario portal con rol customer y contraseña por defecto.
     * Si no se puede crear (email en otra empresa, duplicado, etc.), el cliente
     * ya quedó guardado: no se tumba la fila de importación.
     *
     * @param object $controller Company_Controller
     */
    private static function ensureCustomerPortalUser(
        $controller,
        int $companyId,
        int $customerId,
        string $name,
        string $email
    ): bool {
        $response = $controller->post('user', [
            'company_id' => $companyId,
            'name' => $name !== '' ? $name : $email,
            'email' => $email,
            'password' => '12345678',
            'role' => 'customer',
            'related_id' => $customerId,
        ]);

        if (!$controller->hasError($response)) {
            return true;
        }

        // Best-effort: el cliente ya está importado; el portal user es opcional si el email no se puede usar.
        return true;
    }

    /**
     * @param mixed $response
     */
    private static function extractCustomerIdFromResponse($response): ?int
    {
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        if (!is_array($response)) {
            return null;
        }

        if (isset($response['customer_id']) && (int) $response['customer_id'] > 0) {
            return (int) $response['customer_id'];
        }
        if (isset($response['data']['customer_id']) && (int) $response['data']['customer_id'] > 0) {
            return (int) $response['data']['customer_id'];
        }
        if (isset($response['data']) && is_object($response['data']) && isset($response['data']->customer_id)) {
            return (int) $response['data']->customer_id;
        }

        return null;
    }

    /**
     * @param mixed $response
     */
    private static function extractImportApiErrorMessage($response): string
    {
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        if (!is_array($response)) {
            return self::stringifyImportScalar($response);
        }

        // Envoltorio MVC: ['error' => ..., 'message' => <payload Laravel>]
        $payload = $response;
        if (isset($response['message']) && (is_array($response['message']) || is_object($response['message']))) {
            $payload = is_array($response['message'])
                ? $response['message']
                : (array) json_decode(json_encode($response['message']), true);
        }

        $fromErrors = self::flattenImportValidationErrors($payload['errors'] ?? ($response['errors'] ?? null));
        if ($fromErrors !== '') {
            return $fromErrors;
        }

        if (isset($payload['message']) && is_string($payload['message']) && trim($payload['message']) !== '') {
            return trim($payload['message']);
        }
        if (isset($response['message']) && is_string($response['message']) && trim($response['message']) !== '') {
            return trim($response['message']);
        }
        if (isset($response['error']) && is_string($response['error']) && trim($response['error']) !== '') {
            return trim($response['error']);
        }

        return '';
    }

    /**
     * @param mixed $errors
     */
    private static function flattenImportValidationErrors($errors): string
    {
        if ($errors === null || $errors === '' || $errors === []) {
            return '';
        }
        if (is_object($errors)) {
            $errors = (array) $errors;
        }
        if (!is_array($errors)) {
            $text = self::stringifyImportScalar($errors);

            return ($text !== '' && strcasecmp($text, 'Array') !== 0) ? $text : '';
        }

        $parts = [];
        foreach ($errors as $field => $msgs) {
            $fieldLabel = is_string($field) && !is_numeric($field) ? $field : '';
            if (is_array($msgs) || is_object($msgs)) {
                foreach ((array) $msgs as $m) {
                    $text = self::stringifyImportScalar($m);
                    if ($text === '' || strcasecmp($text, 'Array') === 0) {
                        continue;
                    }
                    $parts[] = $fieldLabel !== '' ? ($fieldLabel . ': ' . $text) : $text;
                }
                continue;
            }
            $text = self::stringifyImportScalar($msgs);
            if ($text === '' || strcasecmp($text, 'Array') === 0) {
                continue;
            }
            $parts[] = $fieldLabel !== '' ? ($fieldLabel . ': ' . $text) : $text;
        }

        $parts = array_values(array_unique(array_filter($parts)));

        return $parts === [] ? '' : implode(' · ', array_slice($parts, 0, 6));
    }

    /**
     * @param mixed $value
     */
    private static function stringifyImportScalar($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_string($value)) {
            return trim($value);
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_array($value) || is_object($value)) {
            $arr = is_array($value) ? $value : (array) json_decode(json_encode($value), true);
            if (isset($arr['message']) && is_string($arr['message'])) {
                return trim($arr['message']);
            }
            if (!empty($arr['errors'])) {
                return self::flattenImportValidationErrors($arr['errors']);
            }
            $encoded = json_encode($arr, JSON_UNESCAPED_UNICODE);

            return is_string($encoded) && $encoded !== '[]' && $encoded !== '{}' ? $encoded : '';
        }

        return trim((string) $value);
    }

    /**
     * @param array<int, string> $cells
     * @return array<int, string>
     */
    private static function normalizeCells(array $cells, bool $forceLegacy = false): array
    {
        $normalized = array_values($cells);
        while (count($normalized) < 29) {
            $normalized[] = '';
        }
        $normalized = array_slice($normalized, 0, max(29, count($normalized)));

        if ($forceLegacy || self::looksLikeLegacyExportRow($normalized)) {
            $normalized = self::remapLegacyExportRow($normalized);
        }

        while (count($normalized) < 29) {
            $normalized[] = '';
        }

        return array_slice($normalized, 0, 29);
    }

    /**
     * Fila del export antiguo: col2 = ID numérico, col7 sin @, col8 con email.
     *
     * @param array<int, string> $cells
     */
    private static function looksLikeLegacyExportRow(array $cells): bool
    {
        $col2 = trim((string) ($cells[2] ?? ''));
        $col7 = trim((string) ($cells[7] ?? ''));
        $col8 = trim((string) ($cells[8] ?? ''));

        if ($col2 === '' || !is_numeric($col2)) {
            return false;
        }

        $col7LooksEmail = $col7 !== '' && strpos($col7, '@') !== false;
        $col8LooksEmail = $col8 !== '' && strpos($col8, '@') !== false;

        if ($col8LooksEmail && !$col7LooksEmail) {
            return true;
        }

        return $col7 !== ''
            && !filter_var($col7, FILTER_VALIDATE_EMAIL)
            && $col8LooksEmail
            && (bool) filter_var($col8, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Convierte una fila del export legacy al orden de la plantilla customercsv.
     *
     * @param array<int, string> $old
     * @return array<int, string>
     */
    public static function remapLegacyExportRow(array $old): array
    {
        return [
            trim((string) ($old[3] ?? '')),  // Id ERP
            trim((string) ($old[0] ?? '')),  // name
            trim((string) ($old[1] ?? '')),  // business_name
            trim((string) ($old[4] ?? '')),  // tax_id
            trim((string) ($old[5] ?? '')),  // discount
            trim((string) ($old[6] ?? '')),  // terms
            trim((string) ($old[7] ?? '')),  // group
            trim((string) ($old[8] ?? '')),  // email
            trim((string) ($old[9] ?? '')),  // phone
            trim((string) ($old[10] ?? '')), // cell
            trim((string) ($old[11] ?? '')), // seller
            trim((string) ($old[12] ?? '')), // notes
            trim((string) ($old[13] ?? '')), // commercial_address
            '',                              // commercial_delivery_address (no existía)
            trim((string) ($old[14] ?? '')), // commercial_country
            trim((string) ($old[15] ?? '')), // commercial_state
            trim((string) ($old[16] ?? '')), // commercial_city
            trim((string) ($old[17] ?? '')), // commercial_zone
            trim((string) ($old[18] ?? '')), // commercial_zip
            trim((string) ($old[19] ?? '')), // dispatch_address
            '',                              // dispatch_delivery_address
            trim((string) ($old[20] ?? '')), // dispatch_country
            trim((string) ($old[21] ?? '')), // dispatch_state
            trim((string) ($old[22] ?? '')), // dispatch_city
            trim((string) ($old[23] ?? '')), // dispatch_zone
            trim((string) ($old[24] ?? '')), // dispatch_zip
            '',                              // dispatch_shipping_notes
            trim((string) ($old[25] ?? '')), // catalog_emails
            trim((string) ($old[26] ?? '')), // customer_status
        ];
    }

    /**
     * @param object $controller
     * @return array<string, mixed>
     */
    private static function &getCache($controller, int $companyId): array
    {
        if (!isset(self::$cacheByCompany[$companyId])) {
            self::$cacheByCompany[$companyId] = self::buildCache($controller, $companyId);
        }

        return self::$cacheByCompany[$companyId];
    }

    /**
     * @param object $controller
     * @return array<string, mixed>
     */
    private static function buildCache($controller, int $companyId): array
    {
        $company = $controller->getObjectFromApi("company/{$companyId}");
        $defaultTermId = is_object($company) ? ($company->default_term_id ?? null) : ($company['default_term_id'] ?? null);
        $defaultGroupId = is_object($company) ? ($company->default_customer_group_id ?? null) : ($company['default_customer_group_id'] ?? null);
        $defaultSellerId = is_object($company) ? ($company->default_seller_id ?? null) : ($company['default_seller_id'] ?? null);

        $termsList = self::fetchList($controller, 'termsofsale', ['status' => 1, 'props' => ['term_id', 'name', 'company_id'], 'all' => true]);
        $groupsList = self::fetchList($controller, 'customergroup', ['status' => 1, 'props' => ['group_id', 'name', 'company_id'], 'all' => true]);
        $sellersList = self::fetchList($controller, 'vendor', ['status' => 1, 'props' => ['id', 'user_id', 'company_id'], 'all' => true, 'relations' => ['user']]);
        $countriesList = self::fetchList($controller, 'country', ['props' => ['id', 'name'], 'all' => true]);
        $statesList = self::fetchList($controller, 'state', ['props' => ['id', 'name', 'country_id'], 'all' => true]);
        $citiesList = self::fetchList($controller, 'city', ['props' => ['id', 'name', 'state_id'], 'all' => true]);

        $termsList = self::filterByCompany($termsList, $companyId);
        $groupsList = self::filterByCompany($groupsList, $companyId);
        $sellersList = self::filterByCompany($sellersList, $companyId);

        $firstTermId = null;
        if ($termsList !== []) {
            $firstTerm = (object) $termsList[0];
            $firstTermId = isset($firstTerm->term_id) ? (int) $firstTerm->term_id : null;
        }
        $firstGroupId = null;
        if ($groupsList !== []) {
            $firstGroup = (object) $groupsList[0];
            $firstGroupId = isset($firstGroup->group_id) ? (int) $firstGroup->group_id : null;
        }

        if ($defaultTermId === null || $defaultTermId === '' || (int) $defaultTermId <= 0) {
            $defaultTermId = $firstTermId;
        }
        if ($defaultGroupId === null || $defaultGroupId === '' || (int) $defaultGroupId <= 0) {
            $defaultGroupId = $firstGroupId;
        }

        return [
            'termsMap' => self::buildNameIdMap($termsList, 'name', 'term_id'),
            'groupsMap' => self::buildNameIdMap($groupsList, 'name', 'group_id'),
            'sellersMap' => self::buildSellerMap($sellersList),
            'countriesMap' => self::buildNameIdMap($countriesList, 'name', 'id'),
            'statesMap' => self::buildNameIdMap($statesList, 'name', 'id'),
            'citiesMap' => self::buildNameIdMap($citiesList, 'name', 'id'),
            'defaultTermId' => $defaultTermId !== null ? (int) $defaultTermId : null,
            'defaultGroupId' => $defaultGroupId !== null ? (int) $defaultGroupId : null,
            'firstTermId' => $firstTermId,
            'firstGroupId' => $firstGroupId,
            'defaultSellerId' => $defaultSellerId,
        ];
    }

    /**
     * @param object $controller
     * @return array<int, object|array>
     */
    private static function fetchList($controller, string $resource, array $params): array
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
     * @return array<int, object|array>
     */
    private static function filterByCompany(array $items, int $companyId): array
    {
        return array_values(array_filter($items, static function ($item) use ($companyId) {
            $item = (object) $item;
            $itemCompanyId = $item->company_id ?? null;

            return (int) $itemCompanyId === $companyId;
        }));
    }

    /**
     * @param array<int, object|array> $items
     * @return array<string, int>
     */
    private static function buildNameIdMap(array $items, string $nameField, string $idField): array
    {
        $map = [];
        foreach ($items as $item) {
            $item = (object) $item;
            $name = trim((string) ($item->{$nameField} ?? ''));
            $id = $item->{$idField} ?? null;
            if ($name !== '' && $id !== null && $id !== '') {
                $map[strtolower($name)] = (int) $id;
                $map[(string) $id] = (int) $id;
            }
        }

        return $map;
    }

    /**
     * @param array<int, object|array> $items
     * @return array<string, int>
     */
    private static function buildSellerMap(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $item = (object) $item;
            $sellerId = $item->id ?? null;
            if ($sellerId === null || $sellerId === '') {
                continue;
            }
            $sellerUser = $item->user ?? null;
            $sellerName = '';
            if (is_object($sellerUser)) {
                $sellerName = trim((string) ($sellerUser->name ?? ''));
            } elseif (is_array($sellerUser)) {
                $sellerName = trim((string) ($sellerUser['name'] ?? ''));
            }
            if ($sellerName !== '') {
                $map[strtolower($sellerName)] = (int) $sellerId;
            }
            $map[(string) $sellerId] = (int) $sellerId;
        }

        return $map;
    }

    /**
     * @param array<int, string> $row
     * @param array<string, mixed> $cache
     * @return array<string, mixed>
     */
    private static function buildCustomerPayload(array $row, int $companyId, array $cache): array
    {
        $businessName = trim((string) ($row[2] ?? ''));
        $emailVal = self::extractFirstEmail((string) ($row[7] ?? ''));
        $nameVal = trim((string) ($row[1] ?? ''));
        if ($nameVal === '' && $businessName !== '') {
            $nameVal = $businessName;
        } elseif ($businessName === '' && $nameVal !== '') {
            $businessName = $nameVal;
        }
        $phoneVal = self::sanitizeOptionalMaxLength($row[8] ?? '', 20);
        $cellPhoneVal = self::sanitizeOptionalMaxLength($row[9] ?? '', 20);
        $termId = self::resolveMappedId(
            $row[5] ?? '',
            $cache['termsMap'] ?? [],
            self::resolveDefaultId($cache, 'term')
        );
        $groupId = self::resolveMappedId(
            $row[6] ?? '',
            $cache['groupsMap'] ?? [],
            self::resolveDefaultId($cache, 'group')
        );

        $customerData = [
            'company_id' => $companyId,
            'cust_id_kor' => trim((string) ($row[0] ?? '')),
            'name' => $nameVal !== '' ? $nameVal : $businessName,
            'business_name' => $businessName,
            'tax_id' => trim((string) ($row[3] ?? '')),
            'discount' => ($row[4] ?? '') !== '' ? (float) $row[4] : 0,
            'phone' => $phoneVal,
            'cell_phone' => $cellPhoneVal,
            'notes' => trim((string) ($row[11] ?? '')),
            'catalog_emails' => ($row[27] ?? '') !== '' ? (int) $row[27] : 0,
            'customer_status' => self::resolveCustomerStatusForPayload($row[28] ?? ''),
            'term_id' => $termId,
            'group_id' => $groupId,
            'sellers' => self::resolveSellers($row[10] ?? '', $cache['sellersMap'] ?? [], $cache['defaultSellerId'] ?? null),
            'commercial_address' => trim((string) ($row[12] ?? '')) !== '' ? trim((string) $row[12]) : ' ',
        ];

        if ($emailVal !== '') {
            $customerData['email'] = $emailVal;
        } else {
            $customerData['email'] = null;
        }

        self::applyOptionalField($customerData, 'commercial_delivery_address', $row[13] ?? '');
        self::applyLocationField($customerData, 'commercial_country', $row[14] ?? '', $cache['countriesMap'] ?? []);
        self::applyLocationIdField($customerData, 'commercial_state', 'commercial_state_id', $row[15] ?? '', $cache['statesMap'] ?? []);
        self::applyLocationIdField($customerData, 'commercial_city', 'commercial_city_id', $row[16] ?? '', $cache['citiesMap'] ?? []);
        self::applyOptionalField($customerData, 'commercial_zone', $row[17] ?? '');
        self::applyOptionalField($customerData, 'commercial_zip_code', $row[18] ?? '');
        self::applyOptionalField($customerData, 'dispatch_address', $row[19] ?? '');
        self::applyOptionalField($customerData, 'dispatch_delivery_address', $row[20] ?? '');
        self::applyLocationField($customerData, 'dispatch_country', $row[21] ?? '', $cache['countriesMap'] ?? []);
        self::applyLocationIdField($customerData, 'dispatch_state', 'dispatch_state_id', $row[22] ?? '', $cache['statesMap'] ?? []);
        self::applyLocationIdField($customerData, 'dispatch_city', 'dispatch_city_id', $row[23] ?? '', $cache['citiesMap'] ?? []);
        self::applyOptionalField($customerData, 'dispatch_zone', $row[24] ?? '');
        self::applyOptionalField($customerData, 'dispatch_zip_code', $row[25] ?? '');
        self::applyOptionalField($customerData, 'dispatch_shipping_notes', $row[26] ?? '');

        return $customerData;
    }

    /**
     * @param array<string, int> $map
     */
    private static function resolveMappedId($value, array $map, $default): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default !== null ? (int) $default : null;
        }
        $key = strtolower($value);
        if (isset($map[$key])) {
            return (int) $map[$key];
        }
        if (is_numeric($value) && isset($map[(string) (int) $value])) {
            return (int) $map[(string) (int) $value];
        }

        return $default !== null ? (int) $default : null;
    }

    /**
     * @param array<string, int> $map
     * @return array<int, int>
     */
    private static function resolveSellers($value, array $map, $defaultSellerId): array
    {
        $value = trim((string) $value);
        $sellers = [];
        if ($value !== '') {
            foreach (explode(',', $value) as $sellerName) {
                $sellerName = trim($sellerName);
                if ($sellerName === '') {
                    continue;
                }
                $key = strtolower($sellerName);
                if (isset($map[$key])) {
                    $sellers[] = (int) $map[$key];
                } elseif (is_numeric($sellerName) && isset($map[(string) (int) $sellerName])) {
                    $sellers[] = (int) $map[(string) (int) $sellerName];
                }
            }
        }
        if ($sellers === [] && !empty($defaultSellerId)) {
            $sellers = [(int) $defaultSellerId];
        }

        return $sellers;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function applyOptionalField(array &$payload, string $field, $value): void
    {
        $value = trim((string) $value);
        if ($value !== '') {
            $payload[$field] = $value;
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, int> $map
     */
    private static function applyLocationField(array &$payload, string $field, $value, array $map): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            $payload[$field] = null;

            return;
        }
        $resolved = self::resolveMappedId($value, $map, null);
        if ($resolved !== null) {
            $payload[$field] = $resolved;
        } elseif (is_numeric($value)) {
            $payload[$field] = (int) $value;
        } else {
            $payload[$field] = null;
        }
    }

    /**
     * Estado y ciudad se validan como texto, pero la importación resuelve IDs.
     * Envía el ID en su campo correcto y deja en blanco el texto incompatible.
     *
     * @param array<string, mixed> $payload
     * @param array<string, int> $map
     */
    private static function applyLocationIdField(
        array &$payload,
        string $textField,
        string $idField,
        $value,
        array $map
    ): void {
        $value = trim((string) $value);
        $resolved = $value !== '' ? self::resolveMappedId($value, $map, null) : null;

        $payload[$textField] = null;
        $payload[$idField] = $resolved !== null ? (int) $resolved : null;
    }

    /**
     * Los campos opcionales demasiado largos se importan en blanco.
     *
     * @param mixed $value
     */
    private static function sanitizeOptionalMaxLength($value, int $maxLength): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || mb_strlen($value, 'UTF-8') > $maxLength) {
            return null;
        }

        return $value;
    }
}
