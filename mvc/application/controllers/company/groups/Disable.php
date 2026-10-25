<?php

class Disable extends Company_Controller
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
        $action = 'disable';
        $updated = $this->put("customergroup/{$group_id}/{$action}");

        if ($this->hasError($updated)) {
            $payload = (is_array($updated) && isset($updated['message']) && is_array($updated['message']))
                ? $updated['message']
                : [];
            $messageKey = isset($payload['message_key']) ? (string) $payload['message_key'] : '';
            if ($messageKey === 'group_has_customers_cannot_disable') {
                $message = $this->formatBlockedByCustomersMessage(
                    $payload,
                    'group_has_customers_cannot_disable',
                    'Customer group has %d assigned customer(s) (%s) and cannot be disabled.'
                );
                $this->jsonError(['message' => $message], $message, 400);
                return;
            }

            $msg = $updated['message'] ?? null;
            if (is_object($msg) && isset($msg->message)) {
                $rawMessage = $msg->message;
            } elseif (is_array($msg) && isset($msg['message'])) {
                $rawMessage = $msg['message'];
            } else {
                $rawMessage = is_string($msg) ? $msg : '';
            }
            // Mensaje traducido según idioma (inglés/español desde cmp-group)
            $message = $this->getDisableErrorMessage($rawMessage);
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
            'message' => $this->groupsLang('group_disabled_success'),
        ]);
    }

    /**
     * Devuelve el mensaje de error traducido según idioma (cmp-group) para desactivar grupo.
     */
    private function getDisableErrorMessage(string $rawMessage): string
    {
        $raw = $rawMessage;
        if (stripos($raw, 'Default customer group') !== false || (stripos($raw, 'default') !== false && stripos($raw, 'cannot be disabled') !== false)) {
            return $this->groupsLang('defecto_nopuede_desactivar');
        }
        if (stripos($raw, 'assigned customers') !== false || stripos($raw, 'clientes asignados') !== false) {
            return $this->groupsLang('group_has_customers_cannot_disable');
        }
        return $raw !== '' ? $raw : $this->groupsLang('no_puede_desactivar');
    }

    /**
     * Construye el mensaje traducido cuando un grupo no puede deshabilitarse
     * porque tiene clientes asignados, incluyendo los nombres de la muestra y el overflow.
     */
    private function formatBlockedByCustomersMessage(array $payload, string $messageKey, string $defaultTemplate): string
    {
        $count = (int) ($payload['customers_count'] ?? 0);
        $names = is_array($payload['customers_names'] ?? null) ? $payload['customers_names'] : [];
        $overflow = (int) ($payload['customers_overflow'] ?? 0);

        $template = $this->langArr[$messageKey]
            ?? (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== ''
                ? $payload['message']
                : $defaultTemplate);

        $andMoreTemplate = $this->langArr['group_customers_and_more'] ?? 'and %d more';

        $namesList = implode(', ', array_map('strval', $names));
        if ($overflow > 0) {
            $suffix = (strpos($andMoreTemplate, '%') !== false)
                ? sprintf($andMoreTemplate, $overflow)
                : $andMoreTemplate . ' ' . $overflow;
            $namesList = $namesList !== '' ? ($namesList . ' ' . $suffix) : $suffix;
        }

        if (substr_count($template, '%') >= 2 && strpos($template, '%s') !== false) {
            return sprintf($template, $count, $namesList);
        }
        if (strpos($template, '%') !== false) {
            return sprintf($template, $count);
        }
        return $template;
    }

}
