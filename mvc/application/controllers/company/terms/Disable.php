<?php

class Disable extends Company_Controller
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

        $action = 'disable';
        $companyId = (int) $this->getCompanyId();
        $updated = $this->put("{$this->entityPath}/{$term_id}/{$action}", [
            'company_id' => $companyId,
            'company' => $companyId,
        ]);

        if ($this->hasError($updated)) {
            $rawMessage = $this->extractErrorMessage($updated);
            $message = $this->getDisableErrorMessage($rawMessage);
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        if (is_object($updated)) {
            $updated = (array)$updated;
        }

        if (!is_array($updated) || !isset($updated['term_id'])) {
            log_message('error', 'Disable term - Respuesta inválida: ' . json_encode($updated));
            $message = $this->termsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 500);
            return;
        }

        if (!isset($updated['term_status'])) {
            $updated['term_status'] = 0;
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
            'message' => $this->termsLang('disabled_success'),
        ]);
    }

    private function extractErrorMessage($updated): string
    {
        if (!is_array($updated) || !isset($updated['message'])) {
            return '';
        }
        $msg = $updated['message'];
        if (is_array($msg) && isset($msg['message'])) {
            return is_string($msg['message']) ? $msg['message'] : '';
        }
        return is_string($msg) ? $msg : '';
    }

    private function getDisableErrorMessage(string $rawMessage): string
    {
        $raw = $rawMessage;
        if ($raw !== '' && (
            stripos($raw, 'No query results for model') !== false
            || stripos($raw, 'Término no encontrado') !== false
            || stripos($raw, 'not found') !== false
        )) {
            return $this->termsLang('term_not_found');
        }
        if ($raw !== '' && (
            stripos($raw, 'default') !== false && (stripos($raw, 'no se puede desactivar') !== false || stripos($raw, 'cannot be disabled') !== false)
            || stripos($raw, 'término default') !== false
        )) {
            return $this->termsLang('defecto_nopuede_desactivar');
        }
        return $raw !== '' ? $raw : $this->termsLang('no_puede_desactivar');
    }
}
