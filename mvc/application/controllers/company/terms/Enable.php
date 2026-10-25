<?php

class Enable extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('termsofsale', 'company/term/', 'terms');
        $this->langArr = $this->getModuleLang('cmp-terms');
    }

    public function index($term_id = null) {
        if ($term_id === null) {
            show_404();
            return;
        }
        return $this->run($term_id);
    }

    public function run($term_id) {
        $term_id = (int) $term_id;
        if ($term_id <= 0) {
            $message = $this->termsLang('term_not_found');
            $this->jsonError(['message' => $message], $message, 404);
            return;
        }

        $action = 'enable';
        $companyId = (int) $this->getCompanyId();
        $updated = $this->put("{$this->entityPath}/{$term_id}/{$action}", [
            'company_id' => $companyId,
            'company' => $companyId,
        ]);

        if ($this->hasError($updated)) {
            $msg = $updated['message'] ?? null;
            $rawMessage = is_object($msg) ? ($msg->message ?? '') : (is_array($msg) ? ($msg['message'] ?? '') : (is_string($msg) ? $msg : ''));
            if ($rawMessage !== '' && (
                stripos($rawMessage, 'No query results for model') !== false
                || stripos($rawMessage, 'Término no encontrado') !== false
                || stripos($rawMessage, 'not found') !== false
            )) {
                $message = $this->termsLang('term_not_found');
            } else {
                $message = $rawMessage !== '' ? $rawMessage : $this->termsLang('no_puede_desactivar');
            }
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        if (is_object($updated)) {
            $updated = (array)$updated;
        }

        if (!is_array($updated) || !isset($updated['term_id'])) {
            log_message('error', 'Enable term - Respuesta inválida: ' . json_encode($updated));
            $message = $this->termsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 500);
            return;
        }

        if (!isset($updated['term_status'])) {
            $updated['term_status'] = 1;
        } else {
            $updated['term_status'] = ($updated['term_status'] == 1 || $updated['term_status'] === true || $updated['term_status'] === '1') ? 1 : 0;
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => (object) $updated,
                'itemIdField' => 'term_id',
                'itemStatusField' => 'term_status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled'),
            ],
            true
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->termsLang('enabled_success'),
        ]);
    }
}
