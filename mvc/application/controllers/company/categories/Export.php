<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Export extends Company_Controller
{
    public function index()
    {
        $lang = $this->getModuleLang('cmp_categorias');
        $raw = file_get_contents('php://input');
        $postData = json_decode((string) $raw, true);
        if (! is_array($postData)) {
            $postData = $this->input->post(null, true);
            if (! is_array($postData)) {
                $postData = [];
            }
        }

        $postData['company_id'] = (int) $this->getCompanyId();
        $postData['file_type'] = 'excel';

        if (empty($postData['separator']) || ! in_array($postData['separator'], ['coma', 'puntoycoma'], true)) {
            $postData['separator'] = 'coma';
        }

        $selectedIds = $postData['category_ids'] ?? [];
        if (! is_array($selectedIds)) {
            $selectedIds = [];
        }
        $selectedIds = array_values(array_filter(array_map('intval', $selectedIds), static fn ($id) => $id > 0));
        $postData['category_ids'] = $selectedIds;

        $allChecked = (string) ($postData['all_checked'] ?? '');
        if (! in_array($allChecked, ['pagina', 'todos'], true) || ($allChecked === 'pagina' && $selectedIds === [])) {
            // Sin selección válida: exportar todo lo filtrado.
            $postData['all_checked'] = 'todos';
        }

        if (empty($postData['filters']) || ! is_array($postData['filters'])) {
            $postData['filters'] = [
                'status' => null,
                'search' => null,
            ];
        }

        $export = $this->apiRawRequest('category/export', $postData, 'POST', [
            'headers' => ['Accept' => '*/*'],
            'timeout' => 120.0,
            'connect_timeout' => 15.0,
        ]);

        if ($this->hasError($export)) {
            $this->jsonError($export, (string) ($lang['error_export'] ?? 'Error exporting'));
            return;
        }

        if (! is_object($export) || ! method_exists($export, 'getHeader')) {
            $this->jsonError(['error' => 'export_failed'], (string) ($lang['error_export'] ?? 'Error exporting'));
            return;
        }

        $disposition = $export->getHeader('Content-Disposition');
        if (! empty($disposition[0])) {
            header('Content-Disposition: '.$disposition[0]);
        } else {
            header('Content-Disposition: attachment; filename="categories_list'.date('Ymd').'.xlsx"');
        }

        $contentType = $export->getHeader('Content-Type');
        if (! empty($contentType[0])) {
            header('Content-Type: '.$contentType[0]);
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        $contentLength = $export->getHeader('Content-Length');
        if (! empty($contentLength[0])) {
            header('Content-Length: '.$contentLength[0]);
        }

        echo $export->getBody()->getContents();
        exit;
    }
}
