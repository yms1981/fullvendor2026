<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'core/ConfigCrudImportSchemas.php';

class Import_template extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
    }

    /**
     * Descarga plantilla Excel de importación (localizada al idioma de la empresa).
     */
    public function download($file = ''): void
    {
        $this->load->library('Spreadsheet_import');
        $basename = Spreadsheet_import::normalizeTemplateBasename((string) $file);
        if ($basename === null) {
            show_404();
        }

        $langTexts = $this->resolveImportTemplateLangTexts($basename);
        $path = Spreadsheet_import::buildLocalizedImportTemplate($basename, $langTexts);
        if ($path === null || !is_file($path)) {
            $path = Spreadsheet_import::resolveImportTemplatePath($basename);
        }

        if ($path === null || !is_readable($path)) {
            show_404();
        }

        $size = filesize($path);
        if ($size === false) {
            show_404();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $basename . '"');
        header('Content-Length: ' . $size);
        header('Cache-Control: private, max-age=3600');
        header('Pragma: public');
        readfile($path);

        if (strpos($path, sys_get_temp_dir()) === 0) {
            @unlink($path);
        }
        exit;
    }

    /**
     * @return array<string, string>
     */
    private function resolveImportTemplateLangTexts(string $basename): array
    {
        $schemaId = $this->mapTemplateToSchemaId($basename);
        $schema = ConfigCrudImportSchemas::get($schemaId);
        $section = (string) ($schema['lang_section'] ?? '');

        $texts = [];
        if ($section !== '' && method_exists($this, 'getLangJsonSection')) {
            $moduleLang = $this->getLangJsonSection($section);
            if (is_array($moduleLang)) {
                $texts = array_merge($texts, $moduleLang);
            }
        }

        $common = method_exists($this, 'getLangJsonSection')
            ? $this->getLangJsonSection('config-crud-tools')
            : [];
        if (is_array($common)) {
            $texts = array_merge($common, $texts);
        }

        return $texts;
    }

    private function mapTemplateToSchemaId(string $basename): string
    {
        $map = [
            'unitscsv.xlsx' => 'units',
            'termscsv.xlsx' => 'terms',
            'groupscsv.xlsx' => 'groups',
            'sellerprofilescsv.xlsx' => 'sellerprofile',
            'currenciescsv.xlsx' => 'currencies',
            'productcsv.xlsx' => 'products',
            'productimagesguide.xlsx' => 'products',
            'customercsv.xlsx' => 'customers',
            'warehousecsv.xlsx' => 'warehouses',
            'userscsv.xlsx' => 'users',
            'new-order-example.xlsx' => 'orders',
        ];

        return $map[strtolower($basename)] ?? '';
    }
}
