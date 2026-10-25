<?php

class EnableEmail extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customer', 'company/customer/', 'customers');
        $this->langArr = $this->getModuleLang('cmp-clientes');
    }

    public function run($customer_id)
    {
        $action = 'enableEmail';
        $updated = $this->put("{$this->entityPath}/{$customer_id}/{$action}");

        if ($this->hasError($updated)) {
            $msg = $updated['message'] ?? null;
            $rawMessage = is_object($msg) ? ($msg->message ?? '') : (is_array($msg) ? ($msg['message'] ?? '') : (is_string($msg) ? $msg : ''));
            $message = $rawMessage ?: ($this->langArr['error_general'] ?? 'An error occurred');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $updated = (object)$updated;
        if (!isset($updated->catalog_emails) || (int)$updated->catalog_emails !== 1) {
            $message = $this->langArr['error_general'] ?? 'An error occurred';
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $html = $this->load->view(
            'company/customer/list/send-catalog_button',
            [
                'listItem' => $updated,
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => array_merge(
                    $this->getModuleLang('cmp-enabled') ?? [],
                    $this->getModuleLang('cmp-clientes') ?? []
                )
            ],
            true
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->langArr['catalog_emails_enabled_success'] ?? 'Catalog sending enabled successfully.'
        ]);
    }
}
