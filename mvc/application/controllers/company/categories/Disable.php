<?php

class Disable extends Company_Controller
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
        $action = 'disable';
        $updated = $this->put("category/{$category_id}/{$action}");

        if ($this->hasError($updated)) {
            $message = $this->resolveCategoryDisableError($updated);
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $updated = (object)$updated;
        if (!isset($updated->category_status) || (int) $updated->category_status !== 0) {
            $message = $this->genericStatusErrorMessage();
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
            'message' => $this->langArr['disabled_success'] ?? 'Category disabled successfully.'
        ]);
    }

    /**
     * Traduce el motivo real del fallo (default vs productos), sin usar siempre error_disabled.
     */
    private function resolveCategoryDisableError($updated): string
    {
        $msg = is_array($updated) ? ($updated['message'] ?? null) : null;
        $rawMessage = '';
        $categoryName = '';

        if (is_object($msg)) {
            $rawMessage = (string) ($msg->message ?? '');
            $categoryName = (string) ($msg->category_name ?? '');
        } elseif (is_array($msg)) {
            $rawMessage = (string) ($msg['message'] ?? '');
            $categoryName = (string) ($msg['category_name'] ?? '');
        } elseif (is_string($msg)) {
            $rawMessage = $msg;
        }

        $normalized = strtolower($rawMessage);

        if ($normalized !== '' && (str_contains($normalized, 'default category')
            || str_contains($normalized, 'status of the default'))) {
            return (string) ($this->langArr['error_disabled'] ?? $rawMessage);
        }

        if ($normalized !== '' && (str_contains($normalized, 'associated products')
            || str_contains($normalized, 'with products')
            || str_contains($normalized, 'product'))) {
            $tpl = (string) ($this->langArr['productos_asignados'] ?? '%s');
            return sprintf($tpl, $categoryName !== '' ? $categoryName : '');
        }

        if ($rawMessage !== '') {
            return $rawMessage;
        }

        return $this->genericStatusErrorMessage();
    }

    private function genericStatusErrorMessage(): string
    {
        if (!empty($this->langArr['error_status_update'])) {
            return (string) $this->langArr['error_status_update'];
        }

        $tools = $this->getLangJsonSection('config-crud-tools');

        return (string) ($tools['message_error_generic'] ?? '');
    }
}
