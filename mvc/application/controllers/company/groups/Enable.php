<?php

class Enable extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customergroup', 'company/group/', 'groups');
        $this->langArr = $this->getModuleLang('cmp-group');
    }

    public function run($group_id) {
        $action = 'enable';
        $updated = $this->put("customergroup/{$group_id}/{$action}");
        if ($this->hasError($updated)) {
            $msg = $updated['message'] ?? null;
            $rawMessage = is_object($msg) ? ($msg->message ?? '') : (is_array($msg) ? ($msg['message'] ?? '') : (is_string($msg) ? $msg : ''));
            $message = $rawMessage ?: $this->groupsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }
        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => (object)$updated,
                'itemIdField' => 'group_id',
                'itemStatusField' => 'group_status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled')
            ],
            true
        );
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->groupsLang('group_enabled_success'),
        ]);
    }
}
