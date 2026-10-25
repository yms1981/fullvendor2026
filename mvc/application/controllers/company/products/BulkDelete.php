<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class BulkDelete extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp-products');
    }

    public function index()
    {
        $postData = json_decode(file_get_contents('php://input'), true);
        if (! is_array($postData)) {
            $postData = [];
        }
        $postData['company_id'] = (int) $this->getCompanyId();

        // Fusionar filtros del listado (mismo criterio que otras acciones masivas).
        foreach (['prdstatus', 'category', 'categoryId', 'photo', 'tag', 'location', 'catalog', 'terms', 'search', 'prsearch'] as $key) {
            if (! array_key_exists($key, $postData) && $this->input->get($key) !== null) {
                $postData[$key] = $this->input->get($key);
            }
        }

        $response = $this->apiDelete('product/bulkDelete', $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['products_delete_error'] ?? 'Error deleting products.');

            return;
        }

        $deleted = is_array($response) ? (int) ($response['deleted'] ?? 0) : 0;
        $failed = is_array($response) ? (int) ($response['failed'] ?? 0) : 0;

        if ($deleted < 1 && $failed < 1) {
            $this->jsonError(
                ['message' => $this->langArr['ningun_producto_eliminado'] ?? ''],
                $this->langArr['ningun_producto_eliminado'] ?? 'No products deleted.',
                422
            );

            return;
        }

        $msg = sprintf(
            (string) ($this->langArr['products_deleted_success'] ?? '%d products deleted'),
            $deleted
        );
        if ($failed > 0) {
            $msg .= ' — '.sprintf(
                (string) ($this->langArr['products_delete_partial'] ?? '%d failed'),
                $failed
            );
        }

        $this->jsonSuccess($msg, is_array($response) ? $response : []);
    }
}
