<?php

class Enable extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('vendor', 'company/seller/', 'sellers');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    public function run($seller_id)
    {
        $action = 'enable';
        $updated = $this->put("{$this->entityPath}/{$seller_id}/{$action}");

        if ($this->hasError($updated)) {
            $msg = $updated['message'] ?? null;
            $rawMessage = is_object($msg) ? ($msg->message ?? '') : (is_array($msg) ? ($msg['message'] ?? '') : (is_string($msg) ? $msg : ''));
            $message = $rawMessage ?: ($this->langArr['error_general'] ?? 'Error updating status.');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $updated = (object)$updated;
        if (!isset($updated->status) || $updated->status != 1) {
            $message = $this->langArr['error_general'] ?? 'Error updating status.';
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => $updated,
                'itemIdField' => 'id',
                'itemStatusField' => 'status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled')
            ],
            true
        );
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->langArr['enabled_success'] ?? 'Enabled successfully.'
        ]);
    }
}
