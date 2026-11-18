<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class UnassignUser extends Company_Controller
{
    public function index()
    {
        $postData = $this->input->post();
        $customerId = (int) ($postData['customer_id'] ?? 0);
        $langArr = $this->getModuleLang('cmp-cliente');

        if ($customerId < 1) {
            $this->jsonError(
                ['message' => $langArr['user_unassign_error'] ?? ''],
                (string) ($langArr['user_unassign_error'] ?? ''),
                422
            );
        }

        $postData['company_id'] = $this->getCompanyId();

        $response = $this->post("customer/unassignUser/{$customerId}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, (string) ($langArr['user_unassign_error'] ?? ''));
        }

        $message = is_object($response) && isset($response->message)
            ? (string) $response->message
            : (string) ($langArr['user_unassigned'] ?? '');

        $this->jsonSuccess($message);
    }
}
