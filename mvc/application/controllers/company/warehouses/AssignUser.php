<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class AssignUser extends Company_Controller
{
    public function index()
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();
        $langArr = $this->getModuleLang('cmp-warehouse');

        $response = $this->post("warehouse/assignUser/{$postData['warehouse_id']}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $langArr['user_assign_error'] ?? '');
        }

        $message = is_object($response) && isset($response->message)
            ? (string) $response->message
            : ($langArr['user_assigned'] ?? '');

        $this->jsonSuccess($message);
    }
}
