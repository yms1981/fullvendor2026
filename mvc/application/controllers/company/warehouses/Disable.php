<?php

require_once APPPATH . 'controllers/company/warehouses/Form.php';

class Disable extends Form
{
    use IsAction;

    public function run($userId = null)
    {
        $userId = (int) $userId;
        if ($userId < 1) {
            return $this->Helper->response_json(['success' => false]);
        }

        $lang = $this->HelperLanguage->status_active();
        $this->put("user/{$userId}/disable");

        return $this->Helper->response_json([
            'success' => true,
            'url' => company_url('warehouses/enable/' . $userId),
            'type_btn' => 'btn-success',
            'class_toremove' => 'btn-warning',
            'data-loading' => $lang['loader-enabling'] ?? '',
            'text_btn' => $lang['option_active'] ?? '',
            'disable' => true,
            'change_inmodal' => true,
        ]);
    }
}
