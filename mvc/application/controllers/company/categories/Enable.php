<?php

class Enable extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }

    public function run($category_id) {
        $action = 'enable';
        $updated = $this->put("category/{$category_id}/{$action}");

        if ($this->hasError($updated)) {
            $msg = $updated['message'] ?? null;
            $rawMessage = is_object($msg) ? ($msg->message ?? '') : (is_array($msg) ? ($msg['message'] ?? '') : (is_string($msg) ? $msg : ''));
            $tools = $this->getLangJsonSection('config-crud-tools');
            $message = $rawMessage
                ?: ($this->langArr['error_status_update'] ?? '')
                ?: ($tools['message_error_generic'] ?? '');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $updated = (object)$updated;

        if (!isset($updated->category_status) || $updated->category_status != 1) {
            $tools = $this->getLangJsonSection('config-crud-tools');
            $message = ($this->langArr['error_status_update'] ?? '')
                ?: ($tools['message_error_generic'] ?? '');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => $updated,
                'itemIdField' => 'cat_id',
                'itemStatusField' => 'category_status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled')
            ],
            true
        );
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->langArr['enabled_success'] ?? 'Category enabled successfully.'
        ]);
    }
}
