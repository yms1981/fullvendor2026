<?php

class Disable extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('user', 'company/user/', 'users');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    public function run($user_id)
    {
        $updated = $this->put("{$this->entityPath}/{$user_id}/disable");

        if ($this->hasError($updated)) {
            $message = $this->extractApiMessage($updated) ?: $this->usersLang('status_update_error');
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        $listItem = $this->normalizeUserStatusItem($updated, $user_id, false);
        $message = $this->usersLang('disabled_success');
        if ($message === '') {
            $message = $this->usersLang('user_updated');
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => $listItem,
                'itemIdField' => 'id',
                'itemStatusField' => 'deleted_at',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled'),
            ],
            true
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $message,
        ]);
        exit;
    }

    private function usersLang(string $key): string
    {
        $moduleLang = isset($this->langArr) && is_array($this->langArr) ? $this->langArr : null;

        return $this->resolveModuleLangString('cmp-user', $key, $moduleLang);
    }

    /**
     * @param mixed $updated
     */
    private function extractApiMessage($updated): string
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

    /**
     * @param mixed $updated
     */
    private function normalizeUserStatusItem($updated, $userId, bool $enabled): object
    {
        if (is_object($updated) && isset($updated->data)) {
            $updated = $updated->data;
        }
        if (is_array($updated) && isset($updated['data']) && is_array($updated['data'])) {
            $updated = $updated['data'];
        }
        if (is_object($updated)) {
            $updated = (array) $updated;
        }
        if (!is_array($updated)) {
            $updated = [];
        }
        if (!isset($updated['id'])) {
            $updated['id'] = (int) $userId;
        }
        $updated['deleted_at'] = $enabled ? null : ($updated['deleted_at'] ?? date('Y-m-d H:i:s'));

        return (object) $updated;
    }
}
