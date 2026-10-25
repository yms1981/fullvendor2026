<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class UnassignUser extends Company_Controller
{
    public function index()
    {
        $postData = $this->input->post();
        $warehouseId = (int) ($postData['warehouse_id'] ?? 0);
        $langArr = $this->getModuleLang('cmp-warehouse');

        if ($warehouseId < 1) {
            $this->jsonError(['message' => $langArr['warehouse_error_save'] ?? ''], $langArr['warehouse_error_save'] ?? '', 422);
        }

        $postData['company_id'] = $this->getCompanyId();

        $response = $this->post("warehouse/unassignUser/{$warehouseId}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $langArr['user_unassign_error'] ?? '');
        }

        $message = is_object($response) && isset($response->message)
            ? (string) $response->message
            : ($langArr['user_unassigned'] ?? '');

        $this->jsonSuccess($message);
    }
}
