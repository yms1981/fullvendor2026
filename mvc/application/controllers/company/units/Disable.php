<?php

class Disable extends Company_Controller
{

    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
    }

    public function run($unit_id)
    {
        $action = 'disable';
        $updated = $this->put("{$this->entityPath}/{$unit_id}/{$action}");

        if ($this->hasError($updated)) {
            $message = $this->resolveUnitDisableErrorMessage($updated);
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $updated = (object) $updated;
        if (isset($updated->status) && (int) $updated->status !== 0) {
            $message = $this->isDefaultUnitResponse($updated)
                ? $this->unitsLang('defecto_nopuede_desactivar')
                : ($this->langArr['error_general'] ?? 'Error updating status.');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => $updated,
                'itemIdField' => 'unit_id',
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
            'message' => $this->langArr['disabled_success'] ?? 'Unit disabled successfully.'
        ]);
    }

    /**
     * @param mixed $updated
     */
    private function resolveUnitDisableErrorMessage($updated): string
    {
        if (is_array($updated) && isset($updated['message']) && is_array($updated['message'])) {
            $payload = $updated['message'];
            $messageKey = trim((string) ($payload['message_key'] ?? ''));
            if ($messageKey === 'defecto_nopuede_desactivar') {
                $localized = $this->unitsLang('defecto_nopuede_desactivar');
                if ($localized !== '') {
                    return $localized;
                }
            }
        }

        return $this->mapUnitDisableErrorMessage($this->extractUnitDisableErrorMessage($updated));
    }

    /**
     * @param mixed $updated
     */
    private function extractUnitDisableErrorMessage($updated): string
    {
        if (!is_array($updated) || !isset($updated['message'])) {
            return '';
        }

        $msg = $updated['message'];
        if (is_object($msg) && isset($msg->message)) {
            return is_string($msg->message) ? $msg->message : '';
        }
        if (is_array($msg) && isset($msg['message'])) {
            return is_string($msg['message']) ? $msg['message'] : '';
        }

        return is_string($msg) ? $msg : '';
    }

    private function mapUnitDisableErrorMessage(string $rawMessage): string
    {
        $raw = trim($rawMessage);
        if ($raw !== '' && (
            stripos($raw, 'default Unit of measure') !== false
            || stripos($raw, 'Unidad por defecto') !== false
            || (stripos($raw, 'default') !== false && stripos($raw, 'cannot be disabled') !== false)
            || (stripos($raw, 'defecto') !== false && stripos($raw, 'desactivar') !== false)
        )) {
            return $this->unitsLang('defecto_nopuede_desactivar');
        }

        return $raw !== '' ? $raw : ($this->langArr['error_general'] ?? 'Error updating status.');
    }

    /**
     * @param object|array<string, mixed> $updated
     */
    private function isDefaultUnitResponse($updated): bool
    {
        if (is_object($updated)) {
            return !empty($updated->default) || !empty($updated->is_default) || !empty($updated->isDefault);
        }
        if (is_array($updated)) {
            return !empty($updated['default']) || !empty($updated['is_default']) || !empty($updated['isDefault']);
        }

        return false;
    }

    private function unitsLang(string $key): string
    {
        return trim((string) ($this->langArr[$key] ?? ''));
    }

}
