<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class AssignUser extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp-cliente');
    }

    public function index()
    {
        $postData = $this->input->post();

        $response = $this->post("customer/assignUser/{$postData['customer_id']}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, (string) ($this->langArr['user_assign_error'] ?? $this->langArr['error_occurred'] ?? 'Error assigning user.'));
        }

        $this->jsonSuccess((string) ($this->langArr['user_assigned'] ?? $this->langArr['success_created'] ?? 'User assigned successfully.'));
    }
}
