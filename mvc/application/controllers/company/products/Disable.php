<?php

class Disable extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
        $this->langArr = $this->getModuleLang('cmp-products');
    }

    public function run($pro_id)
    {
        $action = 'disable';
        try {
            $updated = $this->put("product/{$pro_id}/{$action}");

            if ($this->hasError($updated)) {
                $msg = $updated['message'] ?? null;
                $rawMessage = is_object($msg) ? ($msg->message ?? '') : (is_array($msg) ? ($msg['message'] ?? '') : (is_string($msg) ? $msg : ''));
                $message = $rawMessage ?: ($this->langArr['error_general'] ?? 'Error updating status.');
                $this->jsonError(['message' => $message], $message, 400);
                return;
            }

            $updated = (object)$updated;
            if ($updated->status != 0) {
                $message = $this->langArr['error_general'] ?? 'Error updating status.';
                $this->jsonError(['message' => $message], $message, 400);
                return;
            }

            $html = $this->load->view(
                'components/crud/enable-disable_button',
                [
                    'listItem' => $updated,
                    'itemIdField' => 'pro_id',
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
                'message' => $this->langArr['disabled_success'] ?? 'Product disabled successfully.'
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            if ($e->getCode() == 400) {
                $response = $e->getResponse();
                $body = $response ? (string)$response->getBody() : '';
                $message = $this->langArr['error_general'] ?? 'Error updating status.';
                try {
                    $data = json_decode($body, true);
                    if (!empty($data['message'])) {
                        $message = is_array($data['message']) ? ($data['message']['message'] ?? $message) : $data['message'];
                    }
                } catch (\Throwable $t) {
                    // keep default message
                }
                $this->jsonError(['message' => $message], $message, 400);
                return;
            }
            throw $e;
        }
    }
}
