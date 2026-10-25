<?php

require_once APPPATH . 'controllers/company/warehouses/Form.php';

class Enable extends Form
{
    use IsAction;

    public function run($userId = null)
    {
        $userId = (int) $userId;
        if ($userId < 1) {
            return $this->Helper->response_json(['success' => false]);
        }

        $lang = $this->HelperLanguage->status_active();
        $this->put("user/{$userId}/enable");

        return $this->Helper->response_json([
            'success' => true,
            'url' => company_url('warehouses/disable/' . $userId),
            'class_toremove' => 'btn-success',
            'type_btn' => 'btn-warning',
            'data-loading' => $lang['loader-disabling'] ?? '',
            'text_btn' => $lang['option_desactive'] ?? '',
            'disable' => false,
            'change_inmodal' => true,
        ]);
    }
}
