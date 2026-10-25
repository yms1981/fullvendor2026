<?php

class Disable extends Company_Controller
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
        $action = 'disable';
        $updated = $this->put("{$this->entityPath}/{$customer_id}/{$action}");

        if ($this->hasError($updated)) {
            $msg = $updated['message'] ?? null;
            $rawMessage = is_object($msg) ? ($msg->message ?? '') : (is_array($msg) ? ($msg['message'] ?? '') : (is_string($msg) ? $msg : ''));
            $message = $rawMessage ?: ($this->langArr['error_general'] ?? 'Error updating status.');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $updated = (object)$updated;
        if (isset($updated->customer_status) && $updated->customer_status != 0) {
            $message = $this->langArr['error_general'] ?? 'Error updating status.';
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => $updated,
                'itemIdField' => 'customer_id',
                'itemStatusField' => 'customer_status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled')
            ],
            true
        );
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->langArr['disabled_success'] ?? 'Customer disabled successfully.'
        ]);
    }

}
