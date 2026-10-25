<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'core/ConfigCrudImportSchemas.php';

/**
 * Herramientas compartidas para listados CRUD del menú Configuración:
 * exportar CSV, importar CSV, PDF y descarga de plantilla.
 */
trait ConfigCrudListTools
{
    /** @var string */
    private $configImportLastError = '';

    /**
     * Expone company_id a callbacks de importación (clases externas no pueden usar getCompanyId() protected).
     */
    public function resolveConfigImportCompanyId(): int
    {
        return (int) $this->getCompanyId();
    }

    public function pullConfigImportLastError(): string
    {
        $error = $this->configImportLastError;
        $this->configImportLastError = '';

        return $error;
    }

    public function setConfigImportLastError(string $message): void
    {
        $this->configImportLastError = trim($message);
    }

    /**
     * Garantiza token Sanctum en sesión antes de llamadas API en importación confirmada.
     */
    public function ensureConfigImportApiSession(): bool
    {
        $token = trim((string) ($this->getUserData('token') ?? ''));
        if ($token !== '') {
            return true;
        }

        if (method_exists($this, 'attemptMvcTokenRefresh') && $this->attemptMvcTokenRefresh()) {
            $this->userToken = null;

            return trim((string) ($this->getUserData('token') ?? '')) !== '';
        }

        return false;
    }

    /**
     * @param mixed $response
     */
    protected function extractConfigImportApiErrorMessage($response): string
    {
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        if (!is_array($response)) {
            return 'error';
        }

        $message = $response['message'] ?? null;
        if (is_array($message)) {
            if (isset($message['errors']) && is_array($message['errors'])) {
                $parts = [];
                foreach ($message['errors'] as $err) {
                    $parts[] = is_array($err) ? implode(', ', $err) : (string) $err;
                }
                $joined = implode(' | ', array_filter($parts));

                return $joined !== '' ? $joined : 'error';
            }
            if (isset($message['message']) && is_string($message['message']) && $message['message'] !== '') {
                return $message['message'];
            }
        }

        if (is_string($message) && $message !== '') {
            return $message;
        }

        if (isset($response['errors']) && is_array($response['errors'])) {
            $parts = [];
            foreach ($response['errors'] as $err) {
                $parts[] = is_array($err) ? implode(', ', $err) : (string) $err;
            }
            $joined = implode(' | ', array_filter($parts));

            return $joined !== '' ? $joined : 'error';
        }

        if (isset($response['error']) && is_string($response['error'])) {
            return $response['error'];
        }

        return 'error';
    }

    /**
     * @return array<string, string>
     */
    protected function getConfigCrudLang(): array
    {
        $module = is_array($this->langArr ?? null) ? $this->langArr : [];
        $common = [];

        if (method_exists($this, 'getLangJsonSection')) {
            $common = $this->getLangJsonSection('config-crud-tools');
        }

        if (empty($common) && isset($this->Langmodel) && method_exists($this->Langmodel, 'getMdl')) {
            $langId = $this->resolveConfigCrudLangId();
            $fromApi = $this->Langmodel->getMdl('config-crud-tools', $langId);
            if (is_array($fromApi) && !empty($fromApi)) {
                $common = $fromApi;
            }
        }

        return array_merge(is_array($common) ? $common : [], $module);
    }

    protected function resolveConfigCrudPageTitle(array $lang, string ...$keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($lang[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Marcador para módulos CRUD del menú Configuración (DataGrid + toolbar).
     */
    protected function isConfigCrudModule(): bool
    {
        return true;
    }

    /**
     * Campos editables con doble clic en el DataGrid (sin plantilla de celda).
     *
     * @return array<int, string>
     */
    protected function getConfigCrudInlineEditableFields(): array
    {
        return [];
    }

    protected function getConfigCrudDatagridViewData(): array
    {
        if (!$this->usesConfigCrudDatagrid()) {
            return [];
        }

        $lang = $this->getConfigCrudLang();
        $editableFields = $this->getConfigCrudInlineEditableFields();
        $inlineEdit = [];
        if (!empty($editableFields)) {
            $inlineEdit = [
                'updateUrlBase' => rtrim($this->paginationUrl(), '/') . '/update/',
                'itemIdField' => method_exists($this, 'getItemIdField') ? $this->getItemIdField() : 'id',
                'editableFields' => array_values($editableFields),
                'messages' => [
                    'saved' => trim((string) ($lang['inline_edit_saved'] ?? '')),
                    'error' => trim((string) ($lang['inline_edit_error'] ?? '')),
                    'empty' => trim((string) ($lang['inline_edit_empty'] ?? '')),
                    'hint' => trim((string) ($lang['inline_edit_hint'] ?? '')),
                ],
            ];
        }

        return [
            'useConfigCrudDatagrid' => true,
            'hideServerPagination' => true,
            'hideServerFilter' => true,
            'langDatatable' => $this->Langmodel->getMdl('company-datatable', $this->resolveConfigCrudLangId()),
            'configCrudInlineEdit' => $inlineEdit,
            'configCrudStatusSwitch' => $this->getConfigCrudStatusSwitchConfig(),
            'listCenterAllDataColumns' => false,
            'showRowIndexColumn' => true,
            'showConfigCrudOrderAvatar' => true,
            'configCrudOrderColumnLabel' => $this->resolveConfigCrudToolLabel($lang, 'column_order'),
        ];
    }

    /**
     * Ordena el listado de configuración: registro por defecto primero.
     *
     * @param array<int, object|array<string, mixed>> $items
     * @return array<int, object|array<string, mixed>>
     */
    protected function sortConfigCrudListItems(array $items): array
    {
        if (count($items) < 2) {
            return $items;
        }

        usort($items, function ($a, $b) {
            $aDefault = $this->isConfigCrudDefaultListItem($a);
            $bDefault = $this->isConfigCrudDefaultListItem($b);
            if ($aDefault === $bDefault) {
                return 0;
            }

            return $aDefault ? -1 : 1;
        });

        return $items;
    }

    /**
     * @param object|array<string, mixed> $item
     */
    protected function isConfigCrudDefaultListItem($item): bool
    {
        if (is_array($item)) {
            $item = (object) $item;
        }
        if (!is_object($item)) {
            return false;
        }

        return !empty($item->isDefault) || !empty($item->is_default)
            || (isset($item->default) && (int) $item->default === 1)
            || (isset($item->default_catalog) && (int) $item->default_catalog === 1);
    }

    /**
     * @param object|null $paginatedList
     * @return object|null
     */
    protected function prepareConfigCrudPaginatedList($paginatedList)
    {
        if (!$this->usesConfigCrudDatagrid() || !is_object($paginatedList) || !isset($paginatedList->data)) {
            return $paginatedList;
        }
        if (!is_array($paginatedList->data)) {
            return $paginatedList;
        }

        $paginatedList->data = $this->sortConfigCrudListItems($paginatedList->data);

        return $paginatedList;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfigCrudStatusSwitchConfig(): array
    {
        $lang = $this->getConfigCrudLang();

        return [
            'messages' => [
                'error' => trim((string) ($lang['status_switch_error'] ?? ($lang['message_error_generic'] ?? ''))),
            ],
        ];
    }

    protected function resolveConfigCrudLangId(): int
    {
        if (method_exists($this, 'resolveCompanyPanelLangId')) {
            return $this->resolveCompanyPanelLangId();
        }
        if (function_exists('fv_resolve_company_language_id')) {
            $resolved = fv_resolve_company_language_id();
            if ($resolved >= 1 && $resolved <= 7) {
                return $resolved;
            }
        }
        if (method_exists($this, 'getUserData')) {
            $fromSession = (int) ($this->getUserData('language_id') ?? 0);
            if ($fromSession >= 1 && $fromSession <= 7) {
                return $fromSession;
            }
        }
        if (function_exists('fv_resolve_session_language_id')) {
            return fv_resolve_session_language_id();
        }

        return 1;
    }

    protected function resolveConfigCrudToolLabel(array $lang, string $key): string
    {
        $value = trim((string) ($lang[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }

        $common = method_exists($this, 'getLangJsonSection')
            ? $this->getLangJsonSection('config-crud-tools')
            : [];

        return trim((string) (is_array($common) ? ($common[$key] ?? '') : ''));
    }

    protected function appendConfigCrudListToolbar(string $moduleSlug, string $templateFile): void
    {
        if (!isset($this->listButtonBar) || !($this->listButtonBar instanceof ButtonBar)) {
            $this->listButtonBar = new ButtonBar();
        }

        $lang = $this->getConfigCrudLang();
        $base = company_url($moduleSlug);
        $templateUrl = $this->resolveConfigImportTemplateUrl($templateFile);

        $importLabel = $this->resolveConfigCrudToolLabel($lang, 'import');
        $exportLabel = $this->resolveConfigCrudToolLabel($lang, 'export');
        $templateLabel = $this->resolveConfigCrudToolLabel($lang, 'btn_download_template');
        $pdfLabel = $this->resolveConfigCrudToolLabel($lang, 'btn_pdf');

        // Orden lógico: Agregar (en initListButtonBar) → Importar → Exportar → Plantilla → PDF
        $this->listButtonBar->addLink(
            $importLabel,
            $base . '/csvImport',
            'mdi mdi-import',
            ['class' => 'fv-config-crud-btn fv-config-crud-btn--import', 'title' => $importLabel]
        );
        $this->listButtonBar->addLink(
            $exportLabel,
            $base . '/export',
            'mdi mdi-export',
            ['class' => 'fv-config-crud-btn fv-config-crud-btn--export', 'title' => $exportLabel]
        );
        $this->listButtonBar->addLink(
            $templateLabel,
            $templateUrl,
            'mdi mdi-file-document-outline',
            [
                'download' => '',
                'class' => 'fv-config-crud-btn fv-config-crud-btn--template',
                'title' => $templateLabel,
            ]
        );
        $this->listButtonBar->addLink(
            $pdfLabel,
            $base . '/listPdf',
            'mdi mdi-file-pdf-box',
            ['class' => 'fv-config-crud-btn fv-config-crud-btn--pdf', 'title' => $pdfLabel]
        );
    }

    protected function rejectConfigImportInvalidUpload(array $file, string $listUrl): bool
    {
        if ($this->validateConfigImportUpload($file)) {
            return false;
        }

        $lang = $this->getConfigCrudLang();
        $message = trim((string) ($lang['import_invalid_file'] ?? ($lang['import_no_file'] ?? '')));
        $this->configCrudFlashAndRedirect($message, 'error', $listUrl);

        return true;
    }

    /**
     * URL de plantilla de importación (.xlsx).
     */
    protected function resolveConfigImportTemplateUrl(string $templateFile): string
    {
        return import_template_download_url($templateFile);
    }

    /**
     * @param array<string, mixed> $file
     */
    protected function validateConfigImportUpload(array $file): bool
    {
        $this->load->library('Spreadsheet_import');

        return !empty($file['name'])
            && !empty($file['tmp_name'])
            && is_uploaded_file($file['tmp_name'])
            && Spreadsheet_import::isAllowedImportFilename((string) $file['name']);
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function parseConfigImportFile(string $tmpName, ?string $originalFilename = null): array
    {
        $this->load->library('Spreadsheet_import');

        return Spreadsheet_import::readRows($tmpName, true);
    }

    /**
     * @deprecated Use parseConfigImportFile()
     * @return array<int, array<int, string>>
     */
    protected function parseConfigCsvFile(string $tmpName): array
    {
        return $this->parseConfigImportFile($tmpName);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string>> $rows
     * @deprecated Use sendConfigExcelDownload(); kept for backward compatibility with existing export controllers.
     */
    protected function sendConfigCsvDownload(array $headers, array $rows, string $filename): void
    {
        $filename = preg_replace('/\.csv$/i', '.xlsx', $filename) ?? $filename;
        $this->sendConfigExcelDownload($headers, $rows, $filename);
    }

    /**
     * Descarga un listado en Excel (.xlsx).
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, string|int|float|null>> $rows
     */
    protected function sendConfigExcelDownload(array $headers, array $rows, string $filename): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $filename = trim($filename);
        if ($filename === '') {
            $filename = 'export_' . date('Ymd') . '.xlsx';
        }
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'xlsx') {
            $filename .= '.xlsx';
        }

        $this->load->library('Spreadsheet_import');
        $tmpPath = tempnam(sys_get_temp_dir(), 'fvxlsx_');
        if ($tmpPath === false) {
            show_error('Unable to create temporary export file', 500);
        }

        $xlsxPath = $tmpPath . '.xlsx';
        @unlink($tmpPath);

        $normalizedRows = [];
        foreach ($rows as $row) {
            $normalizedRows[] = array_map(static function ($value) {
                if ($value === null) {
                    return '';
                }

                return is_scalar($value) ? (string) $value : '';
            }, array_values((array) $row));
        }

        if (!Spreadsheet_import::writeTemplate($xlsxPath, array_map('strval', $headers), $normalizedRows)) {
            @unlink($xlsxPath);
            show_error('Unable to generate Excel export', 500);
        }

        $size = @filesize($xlsxPath);
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        if ($size !== false) {
            header('Content-Length: ' . $size);
        }
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($xlsxPath);
        @unlink($xlsxPath);
        exit;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string>> $rows
     */
    protected function sendConfigListPdf(array $headers, array $rows, string $documentTitle, string $filenameStem): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $lang = $this->getConfigCrudLang();
        $companyId = $this->getCompanyId();
        $company = $companyId ? $this->getObjectFromApi('company/' . $companyId) : null;
        if ($this->hasError($company)) {
            $company = (object) ['name' => ''];
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $filenameStem) . '_' . date('Ymd') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $info = [
            'thead' => $headers,
            'tbody' => $rows,
            'title' => $filename,
            'font_size' => '8px',
            'data_extra' => [
                'title' => $documentTitle,
                'bussiness_name' => true,
                'date' => true,
            ],
            'company' => $company,
            'lang' => [
                'company' => $lang['pdf_company_label'] ?? '',
                'date_emition' => $lang['pdf_date_label'] ?? '',
            ],
            'date' => date('Y-m-d'),
        ];

        $html = $this->load->view('company/order/template_exportpdf', $info, true);
        $this->load->library('pdf');
        $this->pdf->set_option('isRemoteEnabled', true);
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', count($headers) > 6 ? 'landscape' : 'portrait');
        $this->pdf->render();
        echo $this->pdf->output();
        exit;
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, string>
     */
    protected function getConfigImportLang(array $schema): array
    {
        $common = $this->resolveConfigImportLangSection('config-crud-tools');
        $section = trim((string) ($schema['lang_section'] ?? ''));
        $sectionLang = $section !== '' ? $this->resolveConfigImportLangSection($section) : [];

        return $this->normalizeConfigImportLang(array_merge($common, $sectionLang));
    }

    /**
     * Carga un bloque de idioma para importación: JSON local → getModuleLang → langmdl API.
     * En Docker solo MVC a veces no hay api/lang montado; el fallback a API es obligatorio.
     *
     * @return array<string, string>
     */
    protected function resolveConfigImportLangSection(string $section): array
    {
        $section = trim($section);
        if ($section === '') {
            return [];
        }

        $loaded = [];
        if (method_exists($this, 'getLangJsonSection')) {
            $fromJson = $this->getLangJsonSection($section);
            if (is_array($fromJson) && $fromJson !== []) {
                $loaded = $fromJson;
            }
        }

        if ($loaded === [] && method_exists($this, 'getModuleLang')) {
            $fromModule = $this->getModuleLang($section);
            if (is_array($fromModule) && $fromModule !== []) {
                $loaded = $fromModule;
            }
        }

        if ($loaded === [] && isset($this->Langmodel) && method_exists($this->Langmodel, 'getMdl')) {
            $langId = $this->resolveConfigCrudLangId();
            $candidates = [$section];
            $alt = str_contains($section, '-')
                ? str_replace('-', '_', $section)
                : str_replace('_', '-', $section);
            if ($alt !== $section) {
                $candidates[] = $alt;
            }
            foreach ($candidates as $candidate) {
                $fromApi = $this->Langmodel->getMdl($candidate, $langId);
                if (is_array($fromApi) && $fromApi !== []) {
                    $loaded = $fromApi;
                    break;
                }
            }
        }

        $out = [];
        foreach ($loaded as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $out[$key] = is_string($value) ? $value : (string) $value;
            }
        }

        return $out;
    }

    /**
     * Convierte valores de error a texto legible (evita el literal PHP "Array").
     *
     * @param mixed $value
     */
    protected function stringifyConfigImportErrorValue($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_string($value)) {
            $text = trim($value);

            return strcasecmp($text, 'Array') === 0 ? '' : $text;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_array($value) || is_object($value)) {
            $arr = is_array($value) ? $value : json_decode(json_encode($value), true);
            if (!is_array($arr)) {
                return '';
            }
            if (isset($arr['message']) && is_string($arr['message']) && trim($arr['message']) !== '') {
                return trim($arr['message']);
            }
            if (isset($arr['reason']) && is_string($arr['reason']) && trim($arr['reason']) !== '') {
                return trim($arr['reason']);
            }
            if (!empty($arr['errors']) && is_array($arr['errors'])) {
                $parts = [];
                foreach ($arr['errors'] as $field => $msgs) {
                    if (is_array($msgs)) {
                        foreach ($msgs as $m) {
                            $t = $this->stringifyConfigImportErrorValue($m);
                            if ($t !== '') {
                                $parts[] = is_string($field) && !is_numeric($field) ? ($field . ': ' . $t) : $t;
                            }
                        }
                    } else {
                        $t = $this->stringifyConfigImportErrorValue($msgs);
                        if ($t !== '') {
                            $parts[] = is_string($field) && !is_numeric($field) ? ($field . ': ' . $t) : $t;
                        }
                    }
                }
                if ($parts !== []) {
                    return implode(' · ', array_slice($parts, 0, 6));
                }
            }
            $flat = [];
            foreach ($arr as $item) {
                $t = $this->stringifyConfigImportErrorValue($item);
                if ($t !== '') {
                    $flat[] = $t;
                }
            }
            if ($flat !== []) {
                return implode(' · ', array_slice($flat, 0, 6));
            }

            return '';
        }

        $text = trim((string) $value);

        return strcasecmp($text, 'Array') === 0 ? '' : $text;
    }

    /**
     * Traduce una clave de error/columna de importación; no deja la clave cruda si hay texto.
     *
     * @param array<string, mixed> $lang
     */
    protected function translateConfigImportMessage(array $lang, string $key): string
    {
        $key = trim($key);
        if ($key === '' || strcasecmp($key, 'Array') === 0) {
            return '';
        }

        $raw = $lang[$key] ?? null;
        if (is_string($raw) || is_numeric($raw)) {
            $label = trim((string) $raw);
            if ($label !== '' && $label !== $key && strcasecmp($label, 'Array') !== 0) {
                return $label;
            }
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $lang
     */
    protected function formatConfigImportRowValidationError(array $row, array $lang): string
    {
        $parts = [];
        foreach ((array) ($row['errors'] ?? []) as $err) {
            $text = $this->stringifyConfigImportErrorValue($err);
            if ($text === '') {
                continue;
            }
            $translated = $this->translateConfigImportMessage($lang, $text);
            if ($translated !== '' && !in_array($translated, $parts, true)) {
                $parts[] = $translated;
            }
        }
        if ($parts === []) {
            return trim((string) ($lang['import_row_error_validation'] ?? ($lang['import_preview_status_error'] ?? 'Validation error')));
        }

        return implode(' · ', $parts);
    }

    /**
     * @param array<string, string> $lang
     * @param array<int, array<string, mixed>> $batchErrors
     * @param array<string, int> $errorCounts
     * @return array{message: string, summary: array<int, array{reason: string, count: int}>, samples: array<int, array<string, mixed>>}
     */
    protected function buildConfigImportResultReport(array $lang, int $loaded, int $failed, int $total, array $batchErrors, array $errorCounts = [], int $skipped = 0): array
    {
        $lines = [];
        $resultTpl = (string) ($lang['import_result'] ?? 'Import finished: %d loaded, %d failed.');
        $lines[] = sprintf($resultTpl, $loaded, $failed);

        $totalTpl = trim((string) ($lang['import_result_total'] ?? ''));
        if ($totalTpl !== '') {
            $lines[] = sprintf($totalTpl, $total);
        }

        if ($skipped > 0) {
            $skippedTpl = trim((string) ($lang['import_result_skipped'] ?? ''));
            if ($skippedTpl !== '') {
                $lines[] = sprintf($skippedTpl, $skipped);
            }
        }

        if ($errorCounts === []) {
            foreach ($batchErrors as $err) {
                $reason = $this->stringifyConfigImportErrorValue($err['message'] ?? '');
                if ($reason === '') {
                    $reason = trim((string) ($lang['import_row_error_generic'] ?? 'Error'));
                }
                if (!isset($errorCounts[$reason])) {
                    $errorCounts[$reason] = 0;
                }
                $errorCounts[$reason]++;
            }
        }

        $normalizedCounts = [];
        foreach ($errorCounts as $reason => $count) {
            $reasonText = $this->stringifyConfigImportErrorValue($reason);
            if ($reasonText === '') {
                $reasonText = trim((string) ($lang['import_row_error_generic'] ?? 'Error'));
            }
            $countNum = is_array($count) || is_object($count)
                ? (int) ($count['count'] ?? 1)
                : (int) $count;
            if ($countNum <= 0) {
                continue;
            }
            $normalizedCounts[$reasonText] = (int) ($normalizedCounts[$reasonText] ?? 0) + $countNum;
        }
        $errorCounts = $normalizedCounts;
        arsort($errorCounts);

        $summary = [];
        foreach ($errorCounts as $reason => $count) {
            $summary[] = ['reason' => (string) $reason, 'count' => (int) $count];
        }

        if ($summary !== []) {
            $heading = trim((string) ($lang['import_error_summary_heading'] ?? ''));
            if ($heading !== '') {
                $lines[] = $heading;
            }
            $reasonTpl = (string) ($lang['import_error_reason_count'] ?? '%d × %s');
            $shown = 0;
            foreach ($summary as $item) {
                if ($shown >= 8) {
                    break;
                }
                $lines[] = sprintf($reasonTpl, (int) $item['count'], (string) $item['reason']);
                $shown++;
            }
            if (count($summary) > 8) {
                $moreTpl = trim((string) ($lang['import_error_more_reasons'] ?? ''));
                if ($moreTpl !== '') {
                    $lines[] = sprintf($moreTpl, count($summary) - 8);
                }
            }
        }

        $samples = array_slice($batchErrors, 0, 12);
        if ($samples !== []) {
            $samplesHeading = trim((string) ($lang['import_error_samples_heading'] ?? ''));
            if ($samplesHeading !== '') {
                $lines[] = $samplesHeading;
            }
            $rowTpl = (string) ($lang['import_error_row_detail'] ?? 'Row %d (%s): %s');
            foreach ($samples as $sample) {
                $lineNo = (int) ($sample['line'] ?? 0);
                $sku = $this->stringifyConfigImportErrorValue($sample['sku'] ?? '');
                if ($sku === '') {
                    $sku = '—';
                }
                $msg = $this->stringifyConfigImportErrorValue($sample['message'] ?? '');
                if ($msg === '') {
                    $msg = trim((string) ($lang['import_row_error_generic'] ?? 'Error'));
                }
                $lines[] = sprintf($rowTpl, $lineNo, $sku, $msg);
            }
            if ($failed > count($samples)) {
                $moreRowsTpl = trim((string) ($lang['import_error_more_rows'] ?? ''));
                if ($moreRowsTpl !== '') {
                    $lines[] = sprintf($moreRowsTpl, $failed - count($samples));
                }
            }
        }

        return [
            'message' => implode("\n", array_filter($lines, static function ($l) {
                return trim((string) $l) !== '';
            })),
            'summary' => $summary,
            'samples' => $samples,
        ];
    }

    /**
     * @param array<string, string> $lang
     * @return array<string, string>
     */
    protected function normalizeConfigImportLang(array $lang): array
    {
        $lang['import_select_file'] = trim((string) ($lang['import_select_file_excel'] ?? ($lang['import_select_file'] ?? '')));
        if (trim((string) ($lang['import_sample_format'] ?? '')) === '') {
            $lang['import_sample_format'] = trim((string) ($lang['btn_download_template'] ?? ($lang['sampledownload'] ?? '')));
        }
        if (trim((string) ($lang['import_upload_title'] ?? '')) === '') {
            $lang['import_upload_title'] = $lang['import_select_file'];
        }
        if (trim((string) ($lang['back'] ?? '')) === '') {
            $lang['back'] = trim((string) ($lang['cancelar'] ?? ($lang['btn-cancelar'] ?? '')));
        }
        if (trim((string) ($lang['import_preview_confirm'] ?? '')) === '') {
            $lang['import_preview_confirm'] = trim((string) ($lang['import_submit'] ?? ''));
        }
        if (trim((string) ($lang['import_preview_button'] ?? '')) === '') {
            $lang['import_preview_button'] = trim((string) ($lang['import_submit'] ?? ''));
        }

        return $lang;
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, string> $lang
     */
    protected function resolveConfigImportPageTitle(array $schema, array $lang): string
    {
        $keys = (array) ($schema['import_title_keys'] ?? ['import_page_title', 'bnImport', 'import']);
        $genericImport = trim((string) ($lang['import'] ?? ''));

        foreach ($keys as $key) {
            $value = trim((string) ($lang[(string) $key] ?? ''));
            if ($value === '') {
                continue;
            }
            if ($key === 'import_page_title' && $genericImport !== '' && strcasecmp($value, $genericImport) === 0) {
                continue;
            }

            return $value;
        }

        return $genericImport;
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, string> $lang
     * @return array<int, array{label: string, help: string}>
     */
    protected function buildConfigImportColumnLegend(array $schema, array $lang): array
    {
        $legend = [];
        foreach ((array) ($schema['columns'] ?? []) as $column) {
            $labelKey = trim((string) ($column['label_key'] ?? ''));
            $helpKey = trim((string) ($column['help_key'] ?? ''));
            if ($labelKey === '' || $helpKey === '') {
                continue;
            }
            $label = $this->translateConfigImportMessage($lang, $labelKey);
            $help = $this->translateConfigImportMessage($lang, $helpKey);
            if ($label === '' || $label === $labelKey || $help === '' || $help === $helpKey) {
                continue;
            }
            $legend[] = ['label' => $label, 'help' => $help];
        }

        return $legend;
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, string> $lang
     */
    protected function resolveConfigImportColumnLabel(array $schema, array $lang, string $labelKey): string
    {
        $label = $this->translateConfigImportMessage($lang, $labelKey);
        if ($label !== '' && $label !== $labelKey) {
            return $label;
        }

        // Último recurso: no mostrar la clave en mayúsculas vía CSS sin sentido.
        return $labelKey !== '' ? $labelKey : '';
    }

    protected function showConfigCsvImportView(string $moduleSlug, string $listUrl, string $successAction): void
    {
        $schemaId = $this->resolveConfigImportSchemaId($moduleSlug);
        $schema = ConfigCrudImportSchemas::get($schemaId);
        if (empty($schema)) {
            show_404();
        }

        $this->handleConfigCrudImportPage($moduleSlug, $listUrl, (string) $schema['template'], $schema);
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function handleConfigCrudImportPage(string $moduleSlug, string $listUrl, string $templateBasename, array $schema): void
    {
        $lang = $this->getConfigImportLang($schema);
        $lang['template_download_url'] = import_template_download_url($templateBasename);
        $lang['import_select_file'] = trim((string) ($lang['import_select_file_excel'] ?? ($lang['import_select_file'] ?? '')));
        $this->langArr = $lang;

        $step = trim((string) $this->input->post('step'));
        if ($step === 'confirm_batch') {
            $this->executeConfigCrudImportConfirmBatch($listUrl, $schema);

            return;
        }
        if ($step === 'confirm') {
            $this->executeConfigCrudImportConfirm($listUrl, $schema);

            return;
        }

        if ($step === 'preview' || (!empty($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'] ?? ''))) {
            $this->processConfigCrudImportPreview($moduleSlug, $listUrl, $templateBasename, $schema);

            return;
        }

        $this->showConfigImportScreen($moduleSlug, $listUrl, $schema, null);
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function executeConfigCrudImportConfirm(string $listUrl, array $schema): void
    {
        $lang = $this->getConfigImportLang($schema);
        $sessionKey = $this->configImportSessionKey($schema);
        $token = trim((string) $this->input->post('import_token'));
        $postedRowsJson = $this->input->post('import_rows', false);
        $postedSig = trim((string) $this->input->post('import_sig', false));
        $stored = $this->loadConfigImportPreview(
            $sessionKey,
            $token,
            is_string($postedRowsJson) ? $postedRowsJson : null,
            $postedSig !== '' ? $postedSig : null
        );

        if ($stored === null) {
            $this->configCrudFlashAndRedirect(
                trim((string) ($lang['import_preview_expired'] ?? ($lang['import_no_file'] ?? ''))),
                'error',
                $listUrl
            );

            return;
        }

        $loaded = 0;
        $failed = 0;
        $batchErrors = [];
        $errorCounts = [];
        $maxStoredErrors = 120;
        $importFn = $schema['import'] ?? null;
        if (!is_callable($importFn)) {
            $this->configCrudFlashAndRedirect(
                trim((string) ($lang['message_error_generic'] ?? '')),
                'error',
                $listUrl
            );

            return;
        }

        $rows = is_array($stored['rows'] ?? null) ? $stored['rows'] : [];
        $total = count($rows);
        $skipped = 0;
        foreach ($rows as $i => $row) {
            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            $lineNo = (int) ($row['line'] ?? ($i + 1));
            $rowLabel = trim((string) ($cells[2] ?? ($cells[0] ?? ($cells[1] ?? ''))));

            if (!empty($row['skip'])) {
                $skipped++;
                continue;
            }

            if (empty($row['valid'])) {
                $failed++;
                $detail = $this->formatConfigImportRowValidationError($row, $lang);
                $errorCounts[$detail] = (int) ($errorCounts[$detail] ?? 0) + 1;
                if (count($batchErrors) < $maxStoredErrors) {
                    $batchErrors[] = [
                        'line' => $lineNo,
                        'sku' => $rowLabel,
                        'message' => $detail,
                        'type' => 'validation',
                    ];
                }
                continue;
            }

            try {
                $ok = (bool) $importFn($this, $cells);
            } catch (\Throwable $e) {
                log_message('error', 'ConfigCrudImport confirm row exception: ' . $e->getMessage());
                $ok = false;
                if (method_exists($this, 'setConfigImportLastError')) {
                    $this->setConfigImportLastError($e->getMessage());
                }
            }

            if ($ok) {
                $loaded++;
                continue;
            }

            $failed++;
            $detail = '';
            if (method_exists($this, 'pullConfigImportLastError')) {
                $detail = (string) $this->pullConfigImportLastError();
            } elseif (method_exists($this, 'pullProductImportLastError')) {
                $detail = (string) $this->pullProductImportLastError();
            }
            $detail = $this->translateConfigImportMessage($lang, $detail);
            $detail = $this->stringifyConfigImportErrorValue($detail);
            if ($detail === '') {
                $detail = trim((string) ($lang['import_row_error_generic'] ?? ($lang['message_error_generic'] ?? '')));
            }
            $errorCounts[$detail] = (int) ($errorCounts[$detail] ?? 0) + 1;
            if (count($batchErrors) < $maxStoredErrors) {
                $batchErrors[] = [
                    'line' => $lineNo,
                    'sku' => $rowLabel,
                    'message' => $detail,
                    'type' => 'import',
                ];
            }
        }

        $this->clearConfigImportPreview($sessionKey, $token);

        if (!empty($schema['single_row']) && $loaded === 0 && $failed === 0) {
            $this->configCrudFlashAndRedirect(
                trim((string) ($lang['import_currency_not_found'] ?? ($lang['import_preview_empty'] ?? ''))),
                'error',
                $listUrl
            );

            return;
        }

        if (isset($schema['after_confirm']) && is_callable($schema['after_confirm'])) {
            $schema['after_confirm']($this, $loaded, $failed);
        }

        $report = $this->buildConfigImportResultReport($lang, $loaded, $failed, $total, $batchErrors, $errorCounts, $skipped);
        $this->session->set_flashdata('import_error_report', $report);
        $this->configCrudFlashAndRedirect(
            $report['message'],
            $failed > 0 ? ($loaded > 0 ? 'warning' : 'error') : 'success',
            $listUrl
        );
    }

    /**
     * Importación por lotes (JSON) para mostrar progreso real en el front.
     *
     * @param array<string, mixed> $schema
     */
    protected function executeConfigCrudImportConfirmBatch(string $listUrl, array $schema): void
    {
        $lang = $this->getConfigImportLang($schema);
        $sessionKey = $this->configImportSessionKey($schema);
        $token = trim((string) $this->input->post('import_token'));
        $postedRowsJson = $this->input->post('import_rows', false);
        $postedSig = trim((string) $this->input->post('import_sig', false));
        $stored = $this->loadConfigImportPreview(
            $sessionKey,
            $token,
            is_string($postedRowsJson) ? $postedRowsJson : null,
            $postedSig !== '' ? $postedSig : null
        );

        $fail = function (string $message) use ($lang): void {
            $text = $message !== '' ? $message : trim((string) ($lang['import_progress_error'] ?? ($lang['message_error_generic'] ?? '')));
            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $text,
                ], JSON_UNESCAPED_UNICODE));
        };

        if ($stored === null) {
            $fail(trim((string) ($lang['import_preview_expired'] ?? ($lang['import_no_file'] ?? ''))));

            return;
        }

        if (method_exists($this, 'ensureConfigImportApiSession') && !$this->ensureConfigImportApiSession()) {
            $fail(trim((string) ($lang['import_row_error_api_auth'] ?? ($lang['import_progress_error'] ?? ''))));

            return;
        }

        $rows = is_array($stored['rows'] ?? null) ? $stored['rows'] : [];
        $total = count($rows);
        $offset = max(0, (int) $this->input->post('offset'));
        $batchSize = (int) ($schema['import_batch_size'] ?? 50);
        $batchSize = min(100, max(5, $batchSize));

        $importFn = $schema['import'] ?? null;
        if (!is_callable($importFn)) {
            $fail(trim((string) ($lang['message_error_generic'] ?? '')));

            return;
        }

        $loaded = (int) ($stored['batch_loaded'] ?? 0);
        $failed = (int) ($stored['batch_failed'] ?? 0);
        $skipped = (int) ($stored['batch_skipped'] ?? 0);
        $batchErrors = is_array($stored['batch_errors'] ?? null) ? $stored['batch_errors'] : [];
        $errorCounts = is_array($stored['batch_error_counts'] ?? null) ? $stored['batch_error_counts'] : [];
        $processedInBatch = 0;
        $maxStoredErrors = 120;

        for ($i = $offset; $i < $total && $processedInBatch < $batchSize; $i++) {
            $row = $rows[$i];
            $processedInBatch++;

            $cells = is_array($row['cells'] ?? null) ? $row['cells'] : [];
            $lineNo = (int) ($row['line'] ?? ($i + 1));
            $rowLabel = trim((string) ($cells[2] ?? ($cells[0] ?? ($cells[1] ?? ''))));

            if (!empty($row['skip'])) {
                $skipped++;
                continue;
            }

            if (empty($row['valid'])) {
                $failed++;
                $detail = $this->formatConfigImportRowValidationError($row, $lang);
                $errorCounts[$detail] = (int) ($errorCounts[$detail] ?? 0) + 1;
                if (count($batchErrors) < $maxStoredErrors) {
                    $batchErrors[] = [
                        'line' => $lineNo,
                        'sku' => $rowLabel,
                        'message' => $detail,
                        'type' => 'validation',
                    ];
                }
                continue;
            }

            try {
                $ok = (bool) $importFn($this, $cells);
            } catch (\Throwable $e) {
                log_message('error', 'ConfigCrudImport batch row exception: ' . $e->getMessage());
                $ok = false;
                if (method_exists($this, 'setConfigImportLastError')) {
                    $this->setConfigImportLastError($e->getMessage());
                }
            }
            if ($ok) {
                $loaded++;
                continue;
            }

            $failed++;
            $detail = '';
            if (method_exists($this, 'pullConfigImportLastError')) {
                $detail = (string) $this->pullConfigImportLastError();
            } elseif (method_exists($this, 'pullProductImportLastError')) {
                $detail = (string) $this->pullProductImportLastError();
            }
            $detail = $this->translateConfigImportMessage($lang, $detail);
            $detail = $this->stringifyConfigImportErrorValue($detail);
            if ($detail === '') {
                $detail = trim((string) ($lang['import_row_error_generic'] ?? ($lang['message_error_generic'] ?? '')));
            }
            $errorCounts[$detail] = (int) ($errorCounts[$detail] ?? 0) + 1;
            if (count($batchErrors) < $maxStoredErrors) {
                $batchErrors[] = [
                    'line' => $lineNo,
                    'sku' => $rowLabel,
                    'message' => $detail,
                    'type' => 'import',
                ];
            }
        }

        $newOffset = $offset + $processedInBatch;
        $done = $newOffset >= $total;

        if ($done) {
            $this->clearConfigImportPreview($sessionKey, $token);

            if (!empty($schema['single_row']) && $loaded === 0 && $failed === 0) {
                $fail(trim((string) ($lang['import_currency_not_found'] ?? ($lang['import_preview_empty'] ?? ''))));

                return;
            }

            if (isset($schema['after_confirm']) && is_callable($schema['after_confirm'])) {
                $schema['after_confirm']($this, $loaded, $failed);
            }

            $report = $this->buildConfigImportResultReport($lang, $loaded, $failed, $total, $batchErrors, $errorCounts, $skipped);
            $message = $report['message'];
            $flashType = $failed > 0 ? ($loaded > 0 ? 'warning' : 'error') : 'success';
            $palette = [
                'success' => '#A5DC86',
                'warning' => '#F8BB86',
                'error' => '#DD6B55',
            ];
            $color = $palette[$flashType] ?? $palette['success'];
            $flashPayload = json_encode([$message, $flashType, $color], JSON_UNESCAPED_UNICODE);
            // El footer de company lee flashdata 'msg'; también se guarda 'message' por compatibilidad.
            $this->session->set_flashdata('msg', $flashPayload);
            $this->session->set_flashdata('message', $flashPayload);
            $this->session->set_flashdata('import_error_report', $report);

            $jsonFlags = JSON_UNESCAPED_UNICODE;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }
            $payload = [
                'success' => true,
                'done' => true,
                'loaded' => $loaded,
                'failed' => $failed,
                'skipped' => $skipped,
                'total' => $total,
                'processed' => $newOffset,
                'redirect' => $listUrl,
                'message' => is_string($message) ? $message : (string) ($report['message'] ?? ''),
                'errors' => array_values(array_slice($batchErrors, 0, 25)),
                'error_summary' => array_values(array_slice($report['summary'], 0, 15)),
                'errors_total' => $failed,
            ];
            $encoded = json_encode($payload, $jsonFlags);
            if ($encoded === false) {
                $encoded = json_encode([
                    'success' => true,
                    'done' => true,
                    'loaded' => $loaded,
                    'failed' => $failed,
                    'skipped' => $skipped,
                    'total' => $total,
                    'processed' => $newOffset,
                    'redirect' => $listUrl,
                    'message' => sprintf(
                        (string) ($lang['import_result'] ?? '%d loaded, %d failed.'),
                        $loaded,
                        $failed
                    ),
                    'errors' => [],
                    'error_summary' => [],
                    'errors_total' => $failed,
                ], JSON_UNESCAPED_UNICODE);
            }

            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output($encoded);

            return;
        }

        $stored['batch_loaded'] = $loaded;
        $stored['batch_failed'] = $failed;
        $stored['batch_skipped'] = $skipped;
        $stored['batch_errors'] = $batchErrors;
        $stored['batch_error_counts'] = $errorCounts;
        $this->persistConfigImportPreview($sessionKey, $stored);

        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode([
                'success' => true,
                'done' => false,
                'loaded' => $loaded,
                'failed' => $failed,
                'total' => $total,
                'processed' => $newOffset,
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function processConfigCrudImportPreview(string $moduleSlug, string $listUrl, string $templateBasename, array $schema): void
    {
        $lang = $this->getConfigImportLang($schema);

        if (empty($_FILES['file']['name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            $this->configCrudFlashAndRedirect($lang['import_no_file'] ?? '', 'error', company_url($moduleSlug . '/csvImport'));
            return;
        }
        if ($this->rejectConfigImportInvalidUpload($_FILES['file'], company_url($moduleSlug . '/csvImport'))) {
            return;
        }

        $parsed = $this->buildConfigImportPreviewRows($_FILES['file']['tmp_name'], $schema, $lang);
        $token = bin2hex(random_bytes(16));
        $sessionKey = $this->configImportSessionKey($schema);
        $compactRows = $this->compactConfigImportRowsForClient($parsed['rows']);
        $rowsJson = (string) json_encode($compactRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sig = hash_hmac('sha256', $token . "\n" . $rowsJson, $this->configImportSigningKey());

        $this->persistConfigImportPreview($sessionKey, [
            'token' => $token,
            'filename' => (string) $_FILES['file']['name'],
            'rows' => $parsed['rows'],
            'created_at' => time(),
        ]);

        $preview = [
            'token' => $token,
            'sig' => $sig,
            'rows_json' => $rowsJson,
            'filename' => (string) $_FILES['file']['name'],
            'columns' => $parsed['columns'],
            'rows' => $parsed['display_rows'],
            'stats' => $parsed['stats'],
        ];

        $this->showConfigImportScreen($moduleSlug, $listUrl, $schema, $preview);
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed>|null $preview
     */
    protected function showConfigImportScreen(string $moduleSlug, string $listUrl, array $schema, ?array $preview): void
    {
        $lang = $this->getConfigImportLang($schema);
        $templateBasename = trim((string) ($schema['template'] ?? ''));
        if ($templateBasename !== '') {
            $lang['template_download_url'] = import_template_download_url($templateBasename);
        }
        $lang['import_page_title'] = $this->resolveConfigImportPageTitle($schema, $lang);
        $columnLegend = $this->buildConfigImportColumnLegend($schema, $lang);

        $this->showContent('company/layouts/config_csv_import', [
            'pageTexts' => $lang,
            'title' => $lang['import_page_title'],
            'importAction' => company_url($moduleSlug . '/csvImport'),
            'confirmAction' => company_url($moduleSlug . '/csvImport'),
            'listUrl' => $listUrl,
            'preview' => $preview,
            'moduleSlug' => $moduleSlug,
            'columnLegend' => $columnLegend,
        ]);
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, string> $lang
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, display_rows: array<int, array<string, mixed>>, stats: array<string, int>}
     */
    protected function buildConfigImportPreviewRows(string $tmpName, array $schema, array $lang): array
    {
        $columns = [];
        foreach ($schema['columns'] as $column) {
            $labelKey = (string) ($column['label_key'] ?? '');
            $columns[] = $this->resolveConfigImportColumnLabel($schema, $lang, $labelKey);
        }
        $columnLegend = $this->buildConfigImportColumnLegend($schema, $lang);

        $validateFn = $schema['validate'] ?? null;
        $rows = [];
        $line = 0;

        $schemaId = (string) ($schema['id'] ?? '');
        $legacyCustomerExport = false;
        $dataRows = null;
        if ($schemaId === 'customers') {
            $this->load->library('Spreadsheet_import');
            $sheet = Spreadsheet_import::readSheet($tmpName);
            $legacyCustomerExport = CustomerCsvImportSupport::isLegacyExportHeaders((array) ($sheet['headers'] ?? []));
            $dataRows = (array) ($sheet['rows'] ?? []);
        }

        foreach (($dataRows !== null ? $dataRows : $this->parseConfigImportFile($tmpName)) as $rawRow) {
            $line++;
            $cells = [];
            foreach ($schema['columns'] as $column) {
                $index = (int) ($column['index'] ?? 0);
                $cells[] = trim((string) ($rawRow[$index] ?? ''));
            }

            if ($this->isConfigImportRowEmpty($cells)) {
                continue;
            }

            // Clientes: remapeo previo del Excel de exportación antiguo antes de validar/mostrar.
            if ($schemaId === 'customers') {
                $cells = CustomerCsvImportSupport::normalizeImportRow($cells, $legacyCustomerExport);
            }

            $validation = ['valid' => true, 'errors' => [], 'error_indexes' => []];
            if (isset($schema['validate_row']) && is_callable($schema['validate_row'])) {
                $validation = $schema['validate_row']($this, $cells);
            } elseif (is_callable($validateFn)) {
                $validation = $validateFn($cells);
            }

            // Si validate_row normaliza/remapea columnas, usar esas celdas en preview y sesión.
            if (isset($validation['cells']) && is_array($validation['cells']) && $validation['cells'] !== []) {
                $cells = array_values($validation['cells']);
            }

            if (!empty($schema['single_row']) && count($rows) >= 1) {
                break;
            }

            $errorKeys = (array) ($validation['errors'] ?? []);
            $errorIndexes = $this->resolveConfigImportErrorIndexes($schema, $errorKeys, (array) ($validation['error_indexes'] ?? []));

            $errorLabels = [];
            foreach ($errorKeys as $errorKey) {
                $translated = $this->translateConfigImportMessage($lang, (string) $errorKey);
                if ($translated !== '') {
                    $errorLabels[] = $translated;
                }
            }

            $isValid = !empty($validation['valid']);
            $isSkip = !empty($validation['skip']);
            $rows[] = [
                'line' => $line,
                'cells' => $cells,
                'valid' => $isValid,
                'skip' => $isSkip,
                'errors' => $errorLabels,
                'error_indexes' => $errorIndexes,
                'duplicate' => false,
            ];
        }

        $this->markConfigImportDuplicateRows($rows, $schema, $lang);

        $validCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;
        $skippedCount = 0;
        foreach ($rows as $row) {
            if (!empty($row['duplicate'])) {
                $duplicateCount++;
            }
            if (!empty($row['skip'])) {
                $skippedCount++;
                continue;
            }
            if (!empty($row['valid'])) {
                $validCount++;
            } else {
                $errorCount++;
            }
        }

        return [
            'columns' => $columns,
            'column_legend' => $columnLegend,
            'rows' => $rows,
            'display_rows' => $rows,
            'stats' => [
                'total' => count($rows),
                'valid' => $validCount,
                'errors' => $errorCount,
                'duplicates' => $duplicateCount,
                'skipped' => $skippedCount,
            ],
        ];
    }

    /**
     * @param array<int, string> $cells
     */
    protected function isConfigImportRowEmpty(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<int, string> $errorKeys
     * @param array<int, int> $explicitIndexes
     * @return array<int, int>
     */
    protected function resolveConfigImportErrorIndexes(array $schema, array $errorKeys, array $explicitIndexes = []): array
    {
        $indexes = array_map('intval', $explicitIndexes);
        $labelToPos = [];
        foreach ($schema['columns'] as $pos => $column) {
            $labelToPos[(string) ($column['label_key'] ?? '')] = $pos;
        }
        foreach ($errorKeys as $errorKey) {
            if (isset($labelToPos[(string) $errorKey])) {
                $indexes[] = (int) $labelToPos[(string) $errorKey];
            }
        }

        $indexes = array_values(array_unique($indexes));
        sort($indexes);

        return $indexes;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $schema
     * @param array<string, string> $lang
     */
    protected function markConfigImportDuplicateRows(array &$rows, array $schema, array $lang): void
    {
        $indexes = array_map('intval', (array) ($schema['duplicate_key_indexes'] ?? []));
        if ($indexes === []) {
            return;
        }

        $duplicateLabel = trim((string) ($lang['import_row_skipped_duplicate'] ?? ($lang['import_preview_duplicate_error'] ?? '')));
        $seen = [];

        foreach ($rows as $i => &$row) {
            if (!empty($row['skip'])) {
                continue;
            }
            $parts = [];
            foreach ($indexes as $idx) {
                $parts[] = mb_strtolower(trim((string) ($row['cells'][$idx] ?? '')));
            }
            $key = implode("\0", $parts);
            if ($key === '' || trim(str_replace("\0", '', $key)) === '') {
                continue;
            }

            // Primera aparición: se importa. Duplicados siguientes: se omiten.
            if (isset($seen[$key])) {
                $this->flagConfigImportDuplicateRow($row, $indexes, $duplicateLabel);
            } else {
                $seen[$key] = $i;
            }
        }
        unset($row);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, int> $indexes
     */
    protected function flagConfigImportDuplicateRow(array &$row, array $indexes, string $duplicateLabel): void
    {
        $row['valid'] = false;
        $row['duplicate'] = true;
        $row['skip'] = true;
        if ($duplicateLabel !== '' && !in_array($duplicateLabel, (array) ($row['errors'] ?? []), true)) {
            $row['errors'][] = $duplicateLabel;
        }
        $errorIndexes = array_map('intval', (array) ($row['error_indexes'] ?? []));
        foreach ($indexes as $idx) {
            if (!in_array($idx, $errorIndexes, true)) {
                $errorIndexes[] = $idx;
            }
        }
        sort($errorIndexes);
        $row['error_indexes'] = $errorIndexes;
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function configImportSessionKey(array $schema): string
    {
        return 'config_import_' . preg_replace('/[^a-z0-9_]/', '', (string) ($schema['id'] ?? 'generic'));
    }

    /**
     * Persistencia dual sesión + archivo: Memcached regenera/destruye la sesión y pierde
     * la previsualización al confirmar (p. ej. tras document.write del preview por XHR).
     * El directorio application/cache suele no ser escribible por www-data → /tmp.
     *
     * @param array<string, mixed> $payload
     */
    protected function persistConfigImportPreview(string $sessionKey, array $payload): void
    {
        $token = trim((string) ($payload['token'] ?? ''));
        if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            return;
        }

        $payload['created_at'] = (int) ($payload['created_at'] ?? time());
        $this->session->set_userdata($sessionKey, $payload);

        $path = $this->configImportPreviewFilePath($token);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            log_message('error', 'ConfigCrudImport: no se pudo serializar previsualización token=' . $token);

            return;
        }

        $written = @file_put_contents($path, $json, LOCK_EX);
        if ($written === false) {
            log_message('error', 'ConfigCrudImport: no se pudo escribir previsualización en ' . $path);
        } else {
            @chmod($path, 0666);
        }
    }

    /**
     * @param string|null $postedRowsJson JSON exacto firmado en la previsualización
     * @return array<string, mixed>|null
     */
    protected function loadConfigImportPreview(string $sessionKey, string $token, $postedRowsJson = null, ?string $postedSig = null): ?array
    {
        $token = trim($token);
        if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $maxAge = 3600;
        $stored = $this->session->userdata($sessionKey);
        if (is_array($stored)
            && !empty($stored['token'])
            && hash_equals((string) $stored['token'], $token)
            && (time() - (int) ($stored['created_at'] ?? 0)) <= $maxAge
            && isset($stored['rows'])
            && is_array($stored['rows'])) {
            return $stored;
        }

        $path = $this->configImportPreviewFilePath($token);
        if (is_readable($path)) {
            $raw = @file_get_contents($path);
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)
                    && !empty($decoded['token'])
                    && hash_equals((string) $decoded['token'], $token)
                    && (time() - (int) ($decoded['created_at'] ?? 0)) <= $maxAge
                    && isset($decoded['rows'])
                    && is_array($decoded['rows'])) {
                    $this->session->set_userdata($sessionKey, $decoded);

                    return $decoded;
                }
            }
        }

        // Respaldo: JSON de filas firmado en el HTML de previsualización.
        if (is_string($postedRowsJson) && $postedRowsJson !== '' && is_string($postedSig) && $postedSig !== '') {
            $expected = hash_hmac('sha256', $token . "\n" . $postedRowsJson, $this->configImportSigningKey());
            if (hash_equals($expected, $postedSig)) {
                $postedRows = $this->decodePostedConfigImportRowsJson($postedRowsJson);
                if (is_array($postedRows) && $postedRows !== []) {
                    $payload = [
                        'token' => $token,
                        'rows' => $postedRows,
                        'created_at' => time(),
                        'from_client' => true,
                    ];
                    $this->persistConfigImportPreview($sessionKey, $payload);

                    return $payload;
                }
            }
            log_message('error', 'ConfigCrudImport: firma de previsualización inválida token=' . $token);
        }

        return null;
    }

    protected function clearConfigImportPreview(string $sessionKey, string $token): void
    {
        $this->session->unset_userdata($sessionKey);
        $token = trim($token);
        if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            return;
        }
        $path = $this->configImportPreviewFilePath($token);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Directorio escribible por el usuario del servidor web (www-data).
     */
    protected function configImportPreviewStorageDir(): string
    {
        static $resolved = null;
        if (is_string($resolved)) {
            return $resolved;
        }

        $candidates = [
            rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config_import',
            rtrim((string) sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'fv_config_import',
        ];

        foreach ($candidates as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
                @chmod($dir, 0777);
            }
            if (is_dir($dir) && is_writable($dir)) {
                $resolved = $dir;

                return $resolved;
            }
        }

        $resolved = $candidates[1];

        return $resolved;
    }

    protected function configImportPreviewFilePath(string $token): string
    {
        $safe = hash('sha256', $token);

        return $this->configImportPreviewStorageDir() . DIRECTORY_SEPARATOR . $safe . '.json';
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function signConfigImportPreviewPayload(string $token, array $rows): string
    {
        $rowsJson = (string) json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $token . "\n" . $rowsJson, $this->configImportSigningKey());
    }

    protected function configImportSigningKey(): string
    {
        $key = trim((string) $this->config->item('encryption_key'));
        if ($key !== '') {
            return $key;
        }

        return hash('sha256', (string) $this->config->item('sess_cookie_name') . '|' . base_url() . '|fv-config-import-v1');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function compactConfigImportRowsForClient(array $rows): array
    {
        $compact = [];
        foreach ($rows as $row) {
            $compact[] = [
                'line' => (int) ($row['line'] ?? 0),
                'cells' => array_values(array_map('strval', (array) ($row['cells'] ?? []))),
                'valid' => !empty($row['valid']),
                'skip' => !empty($row['skip']),
                'duplicate' => !empty($row['duplicate']),
                'errors' => array_values(array_map('strval', (array) ($row['errors'] ?? []))),
                'error_indexes' => array_values(array_map('intval', (array) ($row['error_indexes'] ?? []))),
            ];
        }

        return $compact;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function decodePostedConfigImportRowsJson(string $rowsJson): ?array
    {
        $decoded = json_decode($rowsJson, true);
        if (!is_array($decoded)) {
            return null;
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = [
                'line' => (int) ($row['line'] ?? 0),
                'cells' => array_values(array_map('strval', (array) ($row['cells'] ?? []))),
                'valid' => !empty($row['valid']),
                'skip' => !empty($row['skip']),
                'duplicate' => !empty($row['duplicate']),
                'errors' => array_values(array_map('strval', (array) ($row['errors'] ?? []))),
                'error_indexes' => array_values(array_map('intval', (array) ($row['error_indexes'] ?? []))),
            ];
        }

        return $rows;
    }

    /**
     * @deprecated Usar decodePostedConfigImportRowsJson
     * @return array<int, array<string, mixed>>|null
     */
    protected function decodePostedConfigImportRows(): ?array
    {
        $raw = $this->input->post('import_rows', false);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        return $this->decodePostedConfigImportRowsJson($raw);
    }

    protected function resolveConfigImportSchemaId(string $moduleSlug): string
    {
        $map = [
            'units' => 'units',
            'terms' => 'terms',
            'groups' => 'groups',
            'sellerprofile' => 'sellerprofile',
            'currencies' => 'currencies',
            'products' => 'products',
            'warehouses' => 'warehouses',
            'customers' => 'customers',
            'categories' => 'categories',
            'users' => 'users',
        ];

        return $map[$moduleSlug] ?? $moduleSlug;
    }

    /**
     * Punto de entrada compartido para CsvImportSuccess (confirmación).
     *
     * @param array<string, mixed> $schema
     */
    protected function runConfigCrudImportConfirm(string $listUrl, array $schema): void
    {
        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm($listUrl, $schema);
    }

    protected function configCrudFlashAndRedirect(string $message, string $type, string $listUrl): void
    {
        $palette = [
            'success' => '#A5DC86',
            'warning' => '#F8BB86',
            'error' => '#DD6B55',
        ];
        $color = $palette[$type] ?? $palette['success'];
        $flashPayload = json_encode([$message, $type, $color], JSON_UNESCAPED_UNICODE);
        $this->session->set_flashdata('msg', $flashPayload);
        $this->session->set_flashdata('message', $flashPayload);
        redirect($listUrl, 'refresh');
    }

    /**
     * @return array<int, object|array>
     */
    protected function fetchConfigCrudApiList(string $entityPath, array $filter = []): array
    {
        $filter['all'] = true;
        if (!isset($filter['per_page']) && !isset($filter['perPage'])) {
            $filter['per_page'] = 5000;
            $filter['perPage'] = 5000;
        }
        if (!isset($filter['company']) && method_exists($this, 'getCompanyId')) {
            $companyId = $this->getCompanyId();
            if ($companyId) {
                $filter['company'] = $companyId;
            }
        }

        $response = $this->getObjectFromApi($entityPath, $filter);
        if (is_object($response) && isset($response->data) && is_array($response->data)) {
            return $this->sortConfigCrudListItems($response->data);
        }
        if (is_array($response) && !isset($response['error'])) {
            return $response;
        }

        return [];
    }

    protected function parseConfigCsvBoolean(?string $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'si', 'sí', 'y', 'on'], true);
    }

    protected function normalizeConfigCrudDate($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            return isset($value['date']) ? substr((string) $value['date'], 0, 10) : '';
        }
        if (is_object($value)) {
            if (isset($value->date)) {
                return substr((string) $value->date, 0, 10);
            }
            if (isset($value->created)) {
                return substr((string) $value->created, 0, 10);
            }
        }

        return substr((string) $value, 0, 10);
    }
}
