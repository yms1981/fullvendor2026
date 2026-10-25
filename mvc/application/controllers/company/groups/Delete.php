<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customergroup', 'company/group/', 'groups');
        $this->langArr = $this->getModuleLang('cmp-group');
    }
    public function run($group_id)
    {
        $response = $this->destroy($group_id);

        if ($this->hasError($response)){
            $payload = (is_array($response) && isset($response['message']) && is_array($response['message']))
                ? $response['message']
                : [];
            $messageKey = isset($payload['message_key']) ? (string) $payload['message_key'] : '';
            if ($messageKey === 'group_has_customers_cannot_delete') {
                $msg = $this->formatBlockedByCustomersMessage(
                    $payload,
                    'group_has_customers_cannot_delete',
                    'Customer group has %d assigned customer(s) (%s) and cannot be deleted.'
                );
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['message' => $msg, 'errors' => []]);
                return;
            }
            $this->jsonError($response, $this->langArr['error_eliminar'] ?? '');
        }

        $successMsg = $this->langArr['deleted_success'] ?? '';
        if ($successMsg === '') {
            $toltip = $this->getLangJsonSection('cmp-toltip');
            $successMsg = (string) ($toltip['success_deleted'] ?? '');
        }
        $this->jsonSuccess($successMsg);
    }

    /**
     * Construye el mensaje traducido cuando un grupo no puede eliminarse/deshabilitarse
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
