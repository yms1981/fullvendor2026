<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class BulkDelete extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }

    public function index()
    {
        $postData = json_decode(file_get_contents('php://input'), true);
        if (! is_array($postData)) {
            $postData = [];
        }
        $postData['company_id'] = (int) $this->getCompanyId();

        $response = $this->apiDelete('category/bulkDelete', $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['categories_delete_error'] ?? 'Error deleting categories.');

            return;
        }

        $deleted = is_array($response) ? (int) ($response['deleted'] ?? 0) : 0;
        $skipped = is_array($response) ? (int) ($response['skipped'] ?? 0) : 0;
        $failed = is_array($response) ? (int) ($response['failed'] ?? 0) : 0;

        if ($deleted < 1 && $skipped < 1 && $failed < 1) {
            $this->jsonError(
                ['message' => $this->langArr['ninguna_categoria_eliminada'] ?? ''],
                $this->langArr['ninguna_categoria_eliminada'] ?? 'No categories deleted.',
                422
            );

            return;
        }

        $msg = sprintf(
            (string) ($this->langArr['categories_deleted_success'] ?? '%d categories deleted'),
            $deleted
        );
        if ($skipped > 0) {
            $msg .= ' — '.sprintf(
                (string) ($this->langArr['categories_delete_partial'] ?? '%d skipped'),
                $skipped
            );
        }
        if ($failed > 0) {
            $msg .= ' — '.sprintf(
                (string) ($this->langArr['categories_delete_error_count'] ?? '%d failed'),
                $failed
            );
        }

        $this->jsonSuccess($msg, is_array($response) ? $response : []);
    }
}
