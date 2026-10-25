<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchWarehouse extends Company_Controller
{
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $term = trim((string) ($this->input->get('term') ?? ''));
        $warehouseParams = [
            'all' => true,
            'orderProperty' => 'name',
            'orderDirection' => 'asc',
            'props' => ['id', 'name', 'company_id'],
        ];
        if ($term !== '') {
            $warehouseParams['name'] = $term;
        }

        $response = $this->getDataFromApi('warehouse', $warehouseParams, true);
        if ($this->hasError($response)) {
            echo json_encode([]);

            return;
        }

        $rows = $this->extractApiListRows($response);
        $out = [];
        foreach ($rows as $row) {
            $item = is_array($row) ? $row : (array) (is_object($row) ? $row : []);
            $id = (int) ($item['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => trim((string) ($item['name'] ?? '')),
            ];
        }

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    }
}
