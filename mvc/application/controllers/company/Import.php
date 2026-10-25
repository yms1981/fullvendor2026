<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Historial de importaciones del panel company.
 * Debe usar Company_Controller + showContent para cargar CSS/layout.
 */
class Import extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
        $this->load->model('langmodel');
    }

    /**
     * @return array<int, object>
     */
    private function fetchCompanyImportHistory(int $companyId): array
    {
        if ($companyId < 1) {
            return [];
        }

        $resp = $this->getDataFromApi('importhistory', [
            'company' => $companyId,
            'all' => true,
            'orderProperty' => 'import_id',
            'orderDirection' => 'DESC',
        ], false);

        $rows = [];
        if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
            $rows = $resp->data;
        } elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
            $rows = $resp['data'];
        } elseif (is_array($resp)) {
            $rows = $resp;
        }

        $list = [];
        foreach ($rows as $row) {
            $list[] = is_object($row) ? $row : (object) $row;
        }

        return $list;
    }

    public function lists()
    {
        $importLang = $this->getLangJsonSection('cmp-admin-import');
        $title = (string) ($importLang['list_page_title'] ?? 'Import history');

        $data = [
            'title' => $title,
            'page' => 'import',
            'subpage' => 'importlist',
            'importListLang' => $importLang,
            'list' => $this->fetchCompanyImportHistory((int) $this->getCompany()),
            'langdef' => $this->getCommonTexts(),
        ];

        $this->showContent('company/import_history_list', $data);
    }
}
